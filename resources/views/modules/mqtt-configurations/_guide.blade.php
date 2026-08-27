<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 class="card-title mb-0"><i class="bx bx-book-open me-2"></i>Panduan MQTT Configuration</h4>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#mqtt-guide-content" aria-expanded="false">
                <i class="bx bx-chevron-down"></i> Tampilkan / Sembunyikan
            </button>
        </div>
        <p class="text-muted mb-0">Panduan lengkap untuk teknisi lapangan membuat atau mengedit konfigurasi MQTT.</p>

        <div class="collapse" id="mqtt-guide-content">
            <hr>
            @include('modules.mqtt-configurations._guide-content')
        </div>
    </div>
</div>
