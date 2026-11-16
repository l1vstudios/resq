@extends('layouts.public-pages')

@section('title', 'About Us')

@section('page-css')
<style>
    body { background: #fff; }
    .public-main::before { display: none; }
    .about-page { max-width: 940px; padding-top: clamp(72px, 10vw, 120px); padding-bottom: 120px; }
    .about-header { padding-bottom: clamp(64px, 9vw, 112px); }
    .about-header h1 { margin: 0 0 32px; font-size: clamp(48px, 7vw, 76px); font-weight: 600; line-height: 1.05; letter-spacing: -.045em; }
    .about-header p { max-width: 790px; margin: 0; color: #34483e; font-size: clamp(19px, 2.4vw, 24px); line-height: 1.55; }
    .about-section { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 32px; padding: 34px 0 40px; border-top: 1px solid #dfe6e1; }
    .about-section h2 { margin: 0; color: #173f31; font-size: 17px; font-weight: 600; line-height: 1.5; }
    .about-section p { max-width: 640px; margin: 0; color: #495a50; font-size: 16px; line-height: 1.8; }
    @media (max-width: 700px) {
        .about-page { padding-top: 56px; padding-bottom: 72px; }
        .about-header { padding-bottom: 64px; }
        .about-header h1 { margin-bottom: 24px; }
        .about-section { grid-template-columns: 1fr; gap: 12px; padding: 26px 0 32px; }
    }
</style>
@endsection

@section('content')
<div class="public-container about-page">
    <header class="about-header">
        <h1>About Us</h1>
        <p>PT Sentinal ResQ Tech adalah perusahaan jasa teknis dan teknologi yang menyediakan solusi terintegrasi, layanan profesional, serta platform digital pada bidang kebencanaan, monitoring lingkungan, geospasial, engineering, dan pengelolaan sistem.</p>
    </header>

    <section class="about-section" aria-labelledby="about-solusi">
        <h2 id="about-solusi">Solusi</h2>
        <p>Sentinal mengembangkan Early Warning System dan Environmental Monitoring System sebagai solusi yang mengintegrasikan kajian, survei, pemetaan, engineering, instrumentasi, telemetry, sistem digital, serta tata kelola operasional.</p>
    </section>

    <section class="about-section" aria-labelledby="about-layanan">
        <h2 id="about-layanan">Layanan profesional</h2>
        <p>Layanan profesional Sentinal mencakup kajian risiko dan tata kelola, riset dan assessment, pemetaan dan analisis spasial, engineering dan integrasi sistem, pengembangan sistem digital, serta dukungan operasi dan pemeliharaan.</p>
    </section>

    <section class="about-section" aria-labelledby="about-platform">
        <h2 id="about-platform">Sentinel EMP</h2>
        <p>Untuk mendukung implementasi dan pengelolaan sistem, Sentinal mengembangkan Sentinel EMP sebagai platform teknologi dan layanan digital untuk integrasi perangkat, data, visualisasi, evaluasi, notifikasi, dan operasi sistem monitoring.</p>
    </section>
</div>
@endsection
