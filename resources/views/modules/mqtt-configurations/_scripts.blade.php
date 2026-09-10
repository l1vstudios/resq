<script>
(() => {
    const csrf = @json(csrf_token());
    const sameOriginUrl = (path) => new URL(path, window.location.origin).toString();
    const gatewayStatusUrl = sameOriginUrl(@json(route('mqtt-gateway.status', [], false)));
    const gatewayStartUrl = sameOriginUrl(@json(route('mqtt-gateway.start', [], false)));
    const gatewayStopUrl = sameOriginUrl(@json(route('mqtt-gateway.stop', [], false)));
    const gatewayRestartUrl = sameOriginUrl(@json(route('mqtt-gateway.restart', [], false)));
    const mqttStatusUrl = sameOriginUrl(@json(route('mqtt-configurations.status', [], false)));

    const badge = document.getElementById('mqtt-gateway-badge');
    const btnStart = document.getElementById('mqtt-gateway-start');
    const btnStop = document.getElementById('mqtt-gateway-stop');
    const btnRestart = document.getElementById('mqtt-gateway-restart');

    function updateGatewayBadge(running) {
        if (!badge) return;
        badge.textContent = running ? 'Gateway Running' : 'Gateway Offline';
        badge.className = 'badge ' + (running ? 'bg-success' : 'bg-secondary');
        if (btnStart) btnStart.disabled = running;
        if (btnStop) btnStop.disabled = !running;
        if (btnRestart) btnRestart.disabled = !running;
    }

    async function checkGatewayStatus() {
        try {
            const res = await fetch(gatewayStatusUrl, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            updateGatewayBadge(data.running === true);
        } catch (e) {
            updateGatewayBadge(false);
        }
    }

    async function gatewayAction(url, button) {
        const original = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i>';
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json();
            if (data.message) alert(data.message);
            await checkGatewayStatus();
        } catch (e) {
            alert('Gagal: ' + (e.message || 'Network error'));
        } finally {
            button.innerHTML = original;
            button.disabled = false;
        }
    }

    if (btnStart) btnStart.addEventListener('click', () => gatewayAction(gatewayStartUrl, btnStart));
    if (btnStop) btnStop.addEventListener('click', () => gatewayAction(gatewayStopUrl, btnStop));
    if (btnRestart) btnRestart.addEventListener('click', () => gatewayAction(gatewayRestartUrl, btnRestart));

    // Check gateway status on load
    checkGatewayStatus();

    const refresh = async () => {
        try {
            const response = await fetch(mqttStatusUrl, {headers: {'Accept':'application/json'}});
            if (!response.ok) return;
            const body = await response.json();
            body.configurations.forEach(item => {
                document.querySelectorAll(`[data-mqtt-row="${item.id}"]`).forEach(row => {
                    const badgeEl = row.querySelector('[data-status]');
                    badgeEl.textContent = item.status;
                    badgeEl.className = `badge ${item.status === 'connected' ? 'bg-success' : (item.status === 'error' ? 'bg-danger' : 'bg-secondary')}`;
                    row.querySelector('[data-error]').textContent = item.last_error || '';
                    row.querySelector('[data-received]').textContent = item.last_received_at || '-';
                    row.querySelector('[data-published]').textContent = item.last_published_at || '-';
                });
            });
            checkGatewayStatus();
        } catch (e) {
            updateGatewayBadge(false);
        }
    };
    document.querySelectorAll('[data-mqtt-refresh]').forEach(button => button.addEventListener('click', refresh));
    document.querySelectorAll('[data-test-url]').forEach(button => button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            const response = await fetch(sameOriginUrl(button.dataset.testUrl), {method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf}});
            const body = await response.json().catch(() => ({}));
            window.alert(body.ok ? (body.message || `Test publish berhasil ke ${body.topic}`) : (body.message || 'Test MQTT gagal.'));
            refresh();
        } catch (e) {
            window.alert('Test MQTT gagal: ' + (e.message || 'Network error'));
        } finally {
            button.disabled = false;
        }
    }));
    if (document.querySelector('[data-mqtt-row]')) window.setInterval(refresh, 5000);

    // Populate live sensor payload section when Edit button is clicked
    document.querySelectorAll('[data-edit-fields][data-edit-form]').forEach(button => {
        button.addEventListener('click', () => {
            try {
                const fields = JSON.parse(atob(button.dataset.editFields || 'e30='));
                const formId = (button.dataset.editForm || '').replace(/^#/, '');
                const liveWrapper = document.getElementById(formId + '-live-payload-wrapper');
                const liveEl = document.getElementById(formId + '-live-payload');
                if (liveWrapper && liveEl) {
                    const payload = fields.current_sensor_payload || '';
                    if (payload && payload !== 'null') {
                        liveEl.textContent = payload;
                        liveWrapper.style.display = '';
                    } else {
                        liveWrapper.style.display = 'none';
                        liveEl.textContent = '';
                    }
                }
            } catch (e) {}
        });
    });
})();
</script>
