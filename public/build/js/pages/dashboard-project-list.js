(function (root) {
    'use strict';

    function DashboardProjectList(options) {
        this.options = options;
        this.document = options.document || root.document;
        this.inFlight = false;
        this.destroyed = false;
        this.generation = 0;
        this.timer = null;
    }

    DashboardProjectList.prototype.isInteracting = function () {
        var active = this.document.activeElement;
        return this.options.container.contains(active) && active && active.matches('a, button, input, select, textarea, [contenteditable]');
    };

    DashboardProjectList.prototype.refresh = function () {
        var options = this.options;
        if (this.destroyed || this.inFlight || this.document.hidden || this.isInteracting()) {
            return Promise.resolve();
        }
        this.inFlight = true;
        var generation = this.generation;
        return (options.fetch || root.fetch)(options.url, {
            headers: { Accept: 'application/json' }, cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) throw new Error('Project list unavailable');
            return response.json();
        }).then(function (data) {
            if (this.destroyed || generation !== this.generation || this.isInteracting()) return;
            if (typeof data.html !== 'string') throw new Error('Invalid project list');
            if (options.container.innerHTML !== data.html) {
                var scroll = options.container.querySelector('[data-project-scroll]');
                var left = scroll ? scroll.scrollLeft : 0;
                var focusScroll = scroll && scroll === this.document.activeElement;
                options.container.innerHTML = data.html;
                scroll = options.container.querySelector('[data-project-scroll]');
                if (scroll) {
                    scroll.scrollLeft = left;
                    if (focusScroll) scroll.focus({ preventScroll: true });
                }
            }
            options.count.textContent = data.count_label;
            if (options.onRefresh) options.onRefresh(data);
            options.status.textContent = 'Urutan diperbarui otomatis setiap 5 detik.';
        }.bind(this)).catch(function () {
            if (!this.destroyed && generation === this.generation) {
                options.status.textContent = 'Pembaruan terputus · menampilkan urutan dan data terakhir.';
            }
        }.bind(this)).finally(function () {
            this.inFlight = false;
        }.bind(this));
    };

    DashboardProjectList.prototype.start = function () {
        if (this.timer !== null) return;
        this.destroyed = false;
        this.timer = root.setInterval(this.refresh.bind(this), 5000);
    };

    DashboardProjectList.prototype.destroy = function () {
        this.destroyed = true;
        this.generation++;
        root.clearInterval(this.timer);
        this.timer = null;
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { DashboardProjectList: DashboardProjectList };
    } else {
        root.DashboardProjectList = DashboardProjectList;
    }
}(globalThis));
