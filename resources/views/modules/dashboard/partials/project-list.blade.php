    <div class="table-responsive" tabindex="0" aria-label="Daftar project" data-project-scroll>
        <table id="dashboard-project-grid" class="table table-hover align-middle dashboard-project-grid">
            <caption class="visually-hidden">Daftar project beserta wilayah, klien, jumlah station, dan tautan peta.</caption>
            <thead>
                <tr>
                    <th scope="col">No.</th><th scope="col">Project</th><th scope="col">Wilayah</th><th scope="col">Klien / Pemilik</th>
                    <th scope="col" class="text-center">Workspace</th><th scope="col" class="text-center">Monitoring</th><th scope="col" class="text-center">Peringatan</th><th scope="col">Kondisi Sensor</th><th scope="col">Status Project</th><th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $project)
                    @php
                        $locations = $project->workspaces->map(fn ($workspace) => collect([$workspace->city, $workspace->province])->filter()->unique()->implode(', '))->filter()->unique();
                        $active = in_array(strtolower($project->status ?? ''), ['active', 'aktif'], true);
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $projects->firstItem() + $loop->index }}</td>
                        <td><a href="{{ route('dashboard.projects.map', $project) }}" class="project-name">{{ $project->displayLabel() }}</a></td>
                        <td class="project-region">{{ $locations->implode(' · ') ?: 'Lokasi belum ditentukan' }}</td>
                        <td>{{ $project->client?->name ?: ($project->owner ?: '—') }}</td>
                        <td class="text-center">{{ $project->workspaces_count }}</td>
                        <td class="text-center">{{ $project->monitoring_stations_count }}</td>
                        <td class="text-center">{{ $project->warning_stations_count }}</td>
                        <td><span class="badge bg-{{ $project->monitoring_tone }}-subtle text-{{ $project->monitoring_tone }}">{{ $project->monitoring_status }}</span></td>
                        <td><span class="badge {{ $active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $project->status ?: 'Belum aktif' }}</span></td>
                        <td><a href="{{ route('dashboard.projects.map', $project) }}" class="btn btn-sm btn-outline-primary text-nowrap" aria-label="Buka peta {{ $project->displayLabel() }}"><i class="bx bx-map-alt me-1" aria-hidden="true"></i>Buka Peta</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center py-5">
                        <i class="bx bx-search-alt display-4 text-muted" aria-hidden="true"></i>
                        <h5 class="mt-3">{{ $totalProjects === 0 ? 'Belum ada project' : ($projects->total() === 0 ? 'Tidak ada project yang sesuai filter' : 'Tidak ada project pada halaman ini') }}</h5>
                        <p class="text-muted mb-0">{{ $totalProjects === 0 ? 'Project yang tersedia untuk akun Anda akan tampil di sini.' : 'Sesuaikan filter atau kembali ke halaman pertama.' }}</p>
                        @if ($totalProjects > 0)
                            <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}" class="btn btn-sm btn-outline-primary mt-3">Halaman Pertama</a>
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="dashboard-grid-footer">
        <div class="d-flex align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="project-per-page" class="text-muted small mb-0 text-nowrap">Baris per halaman</label>
                <select name="per_page" id="project-per-page" form="dashboard-project-filters" class="form-select form-select-sm w-auto">
                    @foreach ([10, 25, 50] as $size)
                        <option value="{{ $size }}" @selected($projects->perPage() === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <small class="text-muted">Menampilkan {{ $projects->firstItem() ?? 0 }}–{{ $projects->lastItem() ?? 0 }} dari {{ $projects->total() }} project</small>
        </div>
        {{ $projects->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
