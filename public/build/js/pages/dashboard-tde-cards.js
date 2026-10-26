(function (root) {
    'use strict';

    function DashboardTdeCards(options) {
        this.options = options;
        this.document = options.document || root.document;
        this.projectIds = [];
        this.generation = 0;
        this.inFlight = false;
        this.destroyed = false;
        this.timer = null;
        this.hasData = false;
    }

    DashboardTdeCards.prototype.setProjects = function (ids) {
        var next = Array.isArray(ids) ? ids.map(Number) : [];
        if (next.join(',') === this.projectIds.join(',')) return Promise.resolve();
        this.projectIds = next;
        this.generation++;
        this.hasData = false;
        this.options.results.innerHTML = '<p class="text-muted mb-0">Memuat evaluasi monitoring station…</p>';
        this.options.status.textContent = 'Memuat hasil TDE…';
        return this.refresh();
    };

    DashboardTdeCards.prototype.refresh = function () {
        if (this.destroyed || this.inFlight || this.document.hidden) return Promise.resolve();
        var options = this.options;
        if (!this.projectIds.length) {
            options.results.innerHTML = '<p class="text-muted mb-0">Tidak ada project untuk ditampilkan.</p>';
            options.status.textContent = 'Belum ada hasil TDE';
            return Promise.resolve();
        }
        var generation = this.generation;
        var ids = this.projectIds.slice();
        var url = options.url + '?' + ids.map(function (id) { return 'project_ids[]=' + encodeURIComponent(id); }).join('&');
        this.inFlight = true;
        return (options.fetch || root.fetch)(url, {
            headers: { Accept: 'application/json' }, cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) throw new Error('TDE unavailable');
            return response.json();
        }).then(function (data) {
            if (this.destroyed || generation !== this.generation) return;
            if (!Array.isArray(data.stations)) throw new Error('Invalid TDE response');
            var stations = data.stations.slice().sort(function (a, b) {
                return ids.indexOf(Number(a.project_id)) - ids.indexOf(Number(b.project_id)) ||
                    String(a.station_code).localeCompare(String(b.station_code), 'id', { numeric: true });
            });
            options.results.innerHTML = stations.length ? stations.map(options.renderCard).join('') :
                '<p class="text-muted mb-0">Belum ada monitoring station pada project di halaman ini.</p>';
            this.hasData = true;
            var date = new Date(data.generated_at);
            options.status.textContent = Number.isNaN(date.getTime()) ? 'Menunggu waktu pembaruan' : 'Diperbarui ' + date.toLocaleTimeString('id-ID');
        }.bind(this)).catch(function () {
            if (!this.destroyed && generation === this.generation) options.status.textContent = this.hasData
                ? 'Pembaruan TDE terputus · menampilkan data terakhir' : 'Hasil TDE gagal dimuat · mencoba lagi otomatis';
        }.bind(this)).finally(function () {
            this.inFlight = false;
            if (!this.destroyed && generation !== this.generation) this.refresh();
        }.bind(this));
    };

    DashboardTdeCards.prototype.start = function () {
        if (this.timer !== null) return;
        this.destroyed = false;
        this.refresh();
        this.timer = root.setInterval(this.refresh.bind(this), 15000);
    };

    DashboardTdeCards.prototype.destroy = function () {
        this.destroyed = true;
        this.generation++;
        root.clearInterval(this.timer);
        this.timer = null;
    };

    if (typeof module !== 'undefined' && module.exports) module.exports = { DashboardTdeCards: DashboardTdeCards };
    else root.DashboardTdeCards = DashboardTdeCards;
}(globalThis));
