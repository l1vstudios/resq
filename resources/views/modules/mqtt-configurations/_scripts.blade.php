<script>
(() => {
    const refresh = async () => {
        const response = await fetch(@json(route('mqtt-configurations.status')), {headers: {'Accept':'application/json'}});
        if (!response.ok) return;
        const body = await response.json();
        body.configurations.forEach(item => {
            document.querySelectorAll(`[data-mqtt-row="${item.id}"]`).forEach(row => {
                const badge = row.querySelector('[data-status]');
                badge.textContent = item.status;
                badge.className = `badge ${item.status === 'connected' ? 'bg-success' : (item.status === 'error' ? 'bg-danger' : 'bg-secondary')}`;
                row.querySelector('[data-error]').textContent = item.last_error || '';
                row.querySelector('[data-received]').textContent = item.last_received_at || '-';
                row.querySelector('[data-published]').textContent = item.last_published_at || '-';
            });
        });
    };
    document.querySelectorAll('[data-mqtt-refresh]').forEach(button => button.addEventListener('click', refresh));
    document.querySelectorAll('[data-test-url]').forEach(button => button.addEventListener('click', async () => {
        button.disabled = true;
        const response = await fetch(button.dataset.testUrl, {method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())}});
        const body = await response.json().catch(() => ({}));
        window.alert(body.ok ? (body.message || `Test publish berhasil ke ${body.topic}`) : (body.message || 'Test MQTT gagal.'));
        button.disabled = false;
        refresh();
    }));
    if (document.querySelector('[data-mqtt-row]')) window.setInterval(refresh, 5000);
})();
</script>
