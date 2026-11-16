@extends('layouts.public-pages')

@section('title', 'About Us')

@section('page-css')
<style>
    .about-page { max-width: 940px; padding-top: 12px; padding-bottom: 32px; }
    .about-header { padding-bottom: 28px; }
    .about-header h1 { margin: 0 0 18px; color: #34483e; font-size: clamp(34px, 4vw, 42px); font-weight: 600; line-height: 1.15; }
    .about-header p { max-width: 790px; margin: 0; color: #34483e; font-size: 18px; line-height: 1.65; }
    .about-section { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 32px; padding: 18px 0 22px; border-top: 1px solid #c9d5cd; }
    .about-section h2 { margin: 0; color: #34483e; font-size: 17px; font-weight: 600; line-height: 1.5; }
    .about-section p { max-width: 640px; margin: 0; color: #40594c; font-size: 15px; line-height: 1.8; }
    @media (max-width: 700px) {
        .about-page { padding-top: 12px; padding-bottom: 32px; }
        .about-header { padding-bottom: 28px; }
        .about-header h1 { margin-bottom: 16px; }
        .about-header p { font-size: 16px; }
        .about-section { grid-template-columns: 1fr; gap: 10px; padding: 20px 0 24px; }
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
