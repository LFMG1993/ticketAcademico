<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require '../Core/Database.php';
require '../Core/Redis.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['respuesta'])) {
    $id = $_POST['id'];
    $respuesta = $_POST['respuesta'];
    $stmt = $pdo->prepare("UPDATE tickets SET respuesta = ?, estado = 'resuelto', atendido_por = ? WHERE id = ?");
    $stmt->execute([$respuesta, $_SESSION['admin'], $id]);

    if ($redis) {
        $redis->del('tickets:recientes');
        $redis->lPush('tickets:log', date('H:i:s') . " — Ticket #$id respondido por " . $_SESSION['admin']);
        $redis->lTrim('tickets:log', 0, 19);
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        exit(json_encode(['success' => true]));
    }
}

$stmt = $pdo->query("SELECT * FROM tickets ORDER BY creado_en DESC");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$redisActivo = $redis !== null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Gestión - TicketFlow</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<nav>
    <div><strong>TicketFlow Admin</strong> | Bienvenido, <?= $_SESSION['admin'] ?></div>
    <div><a href="logout.php">Salir 🚪</a></div>
</nav>

<div class="container">
    <h2>Gestión de Solicitudes</h2>

    <!-- TICKETS ASYNC  -->
    <div id="tickets-section" class="card" style="padding:0;overflow:hidden">
        <!-- cargado por JS -->
        <div style="padding:2rem;text-align:center;color:#94a3b8">Cargando tickets…</div>
    </div>

    <!-- PANEL REDIS ASYNC -->
    <div class="card redis-section">
        <h3 style="display:flex;justify-content:space-between;align-items:center">
            <span>📊 Redis en Vivo</span>
            <span id="redis-pulse" style="font-size:.75rem;color:#94a3b8">actualizando…</span>
        </h3>
        <div id="redis-stats" class="redis-grid">
            <div class="redis-stat">
                <div class="num">…</div>
                <div class="lbl">Contador tickets</div>
            </div>
            <div class="redis-stat">
                <div class="num">…</div>
                <div class="lbl">Caché tickets:recientes</div>
            </div>
            <div class="redis-stat">
                <div class="num">…</div>
                <div class="lbl">Sesiones admin activas</div>
            </div>
        </div>
        <h4 style="margin-bottom:.5rem">🔔 Últimas 10 acciones</h4>
        <div id="redis-log"><p style="color:#94a3b8">Cargando…</p></div>
        <h4 style="margin-top:1.5rem;margin-bottom:.5rem">🔑 Estado de claves Redis</h4>
        <div id="redis-keys"><p style="color:#94a3b8">Cargando…</p></div>
    </div>
</div>

<script>
    // helpers
    function estadoBadge(estado) {
        const map = {pendiente: 'badge-pendiente', resuelto: 'badge-resuelto', 'en proceso': 'badge-en-proceso'};
        return `<span class="badge ${map[estado] || ''}">${estado}</span>`;
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    // tickets
let estaEnviando = false;
async function cargarTickets() {
    try {
        const r = await fetch('../Controllers/Api/tickets.php');
        if (r.status === 401) { window.location = 'login.php'; return; }
        if (!r.ok) return;
        const tickets = await r.json();
        if (tickets.error) { console.error('Tickets API:', tickets.error); return; }

            if (estaEnviando) return;

            // CAPTURAR ESTADO ACTUAL: Guardar lo que el usuario está escribiendo
            const borradores = {};
            let idEnFoco = null;
            let posicionCursor = 0;

            document.querySelectorAll('#tickets-section textarea').forEach(tx => {
                const id = tx.getAttribute('data-id');
                if (tx.value) borradores[id] = tx.value;
                if (document.activeElement === tx) {
                    idEnFoco = id;
                    posicionCursor = tx.selectionStart;
                }
            });

            const sec = document.getElementById('tickets-section');

            if (!tickets.length) {
                sec.innerHTML = '<p style="padding:2rem;color:#94a3b8">Aún no hay tickets.</p>';
                return;
            }

            let rows = tickets.map(t => `
            <tr>
                <td><strong>#${t.id}</strong></td>
                <td><strong>${esc(t.nombre)}</strong><br><small>${esc(t.correo)}</small></td>
                <td><span class="badge">${esc(t.categoria)}</span><br>${esc(t.descripcion)}</td>
                <td>${estadoBadge(t.estado)}</td>
                <td id="td-accion-${t.id}">${t.estado === 'pendiente'
                ? `<form onsubmit="enviarRespuesta(event, ${t.id})">
                           <input type="hidden" name="id" value="${t.id}">
                           <textarea 
                                name="respuesta" 
                                data-id="${t.id}" 
                                placeholder="Escribe la respuesta..." 
                                required 
                                style="margin-bottom:5px"></textarea>
                           <button type="submit" name="responder" style="padding:5px 10px;font-size:12px">Responder</button>
                       </form>`
                : `<div style="font-size:12px;color:#666">
                           <strong>Respuesta:</strong> ${esc(t.respuesta)}<br>
                           <small>Por: ${esc(t.atendido_por)}</small>
                       </div>`
            }</td>
            </tr>`).join('');

            sec.innerHTML = `
            <table>
                <thead><tr><th>Ticket</th><th>Estudiante</th><th>Asunto</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>`;

            // RESTAURAR ESTADO: Volver a poner el texto y el foco
            document.querySelectorAll('#tickets-section textarea').forEach(tx => {
                const id = tx.getAttribute('data-id');
                if (borradores[id]) {
                    tx.value = borradores[id];
                }
                if (id === idEnFoco) {
                    tx.focus();
                    tx.setSelectionRange(posicionCursor, posicionCursor);
                }
            });

        } catch (e) {
            console.warn('tickets fetch error', e);
        }
    }

    async function enviarRespuesta(event, id) {
        event.preventDefault();
        const form = event.target;
        const btn = form.querySelector('button');
        const textarea = form.querySelector('textarea');
        const respuesta = textarea.value;

        estaEnviando = true;
        btn.disabled = true;
        btn.textContent = 'Enviando...';

        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('respuesta', respuesta);
            formData.append('responder', '1');

            const r = await fetch('dashboard.php', {
                method: 'POST',
                body: formData
            });

            if (r.ok) {
                await cargarTickets();
            }
        } catch (e) {
            alert('Error al enviar respuesta');
            btn.disabled = false;
            btn.textContent = 'Responder';
        } finally {
            estaEnviando = false;
        }
    }

    // redis panel
async function cargarRedis() {
    try {
        const r = await fetch('../Controllers/Api/redis.php');
        if (r.status === 401) { window.location = 'login.php'; return; }
        if (!r.ok) return;
        const d = await r.json();
        if (d.error) { console.error('Redis API:', d.error); return; }

            const pulse = document.getElementById('redis-pulse');
            pulse.textContent = '⟳ ' + new Date().toLocaleTimeString();

            // Stats
            const cacheOk = d.cacheTTL > 0;
            document.getElementById('redis-stats').innerHTML = `
            <div class="redis-stat">
                <div class="num">${d.contador}</div>
                <div class="lbl">Contador tickets</div>
            </div>
            <div class="redis-stat" style="background:${cacheOk ? '#065f46' : '#7f1d1d'}">
                <div class="num" style="font-size:1.2rem">${cacheOk ? '✓ ACTIVA (' + d.cacheTTL + 's)' : '✗ EXPIRADA'}</div>
                <div class="lbl">Caché tickets:recientes</div>
            </div>
            <div class="redis-stat" style="background:#1e3a5f">
                <div class="num">${d.sesiones}</div>
                <div class="lbl">Sesiones admin activas</div>
            </div>`;

            // Log
            document.getElementById('redis-log').innerHTML = d.log.length
                ? d.log.map(e => `<div class="log-item">${esc(e)}</div>`).join('')
                : '<p style="color:#94a3b8">Sin actividad aún.</p>';

            // Keys table
            let kRows = d.claves.map(c => `
            <tr>
                <td><code>${esc(c.key)}</code></td>
                <td>${esc(c.tipo)}</td>
                <td>${esc(c.ttl)}</td>
                <td>${esc(String(c.valor))}</td>
            </tr>`).join('');
            document.getElementById('redis-keys').innerHTML =
                `<table style="font-size:.85rem">
                <thead><tr><th>Clave</th><th>Tipo</th><th>TTL</th><th>Valor</th></tr></thead>
                <tbody>${kRows}</tbody>
             </table>`;
        } catch (e) {
            console.warn('redis fetch error', e);
        }
    }

    cargarTickets();
    cargarRedis();
    setInterval(cargarRedis, 5000);
    setInterval(cargarTickets, 10000);
</script>
</body>
</html>