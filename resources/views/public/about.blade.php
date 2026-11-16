@extends('layouts.public-pages')

@section('title', 'About Us')

@section('page-css')
<style>
    .about-hero { padding: clamp(64px, 10vw, 132px) 0 68px; }
    .about-hero .page-intro { max-width: 820px; color: #344a3e; }
    .about-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; padding-bottom: 90px; }
    .about-card { min-height: 250px; padding: clamp(28px, 4vw, 42px); }
    .about-card:first-child { grid-column: 1 / -1; min-height: 205px; background: #173f31; color: #fff; border-color: #173f31; }
    .about-card-index { display: block; margin-bottom: 32px; color: #2b9170; font-size: 12px; font-weight: 800; letter-spacing: .12em; }
    .about-card:first-child .about-card-index { color: #88dcb9; }
    .about-card h2 { margin: 0 0 16px; font-size: clamp(22px, 3vw, 29px); line-height: 1.2; letter-spacing: -.025em; }
    .about-card p { max-width: 850px; margin: 0; color: #52645b; font-size: 15px; line-height: 1.8; }
    .about-card:first-child p { color: #e0f0e7; }
    .about-contact { display: inline-flex; align-items: center; gap: 8px; margin-top: 24px; color: #d7f9e6; text-decoration: none; font-weight: 700; }
    .about-contact:hover { color: #fff; }
    @media (max-width: 700px) {
        .about-hero { padding: 58px 0 42px; }
        .about-grid { grid-template-columns: 1fr; gap: 14px; padding-bottom: 60px; }
        .about-card, .about-card:first-child { grid-column: auto; min-height: 0; }
        .about-card-index { margin-bottom: 22px; }
    }
</style>
@endsection

@section('content')
<div class="public-container">
    <section class="about-hero" aria-labelledby="about-title">
        <p class="page-eyebrow">Mengenal Sentinal</p>
        <h1 class="page-title" id="about-title">About Us</h1>
        <p class="page-intro">PT Sentinal ResQ Tech adalah perusahaan jasa teknis dan teknologi yang menyediakan solusi terintegrasi, layanan profesional, serta platform digital pada bidang kebencanaan, monitoring lingkungan, geospasial, engineering, dan pengelolaan sistem.</p>
    </section>

    <div class="about-grid">
        <section class="public-card about-card">
            <span class="about-card-index">01 / SOLUSI</span>
            <h2>Early Warning System &amp; Environmental Monitoring System</h2>
            <p>Sentinal mengembangkan Early Warning System dan Environmental Monitoring System sebagai solusi yang mengintegrasikan kajian, survei, pemetaan, engineering, instrumentasi, telemetry, sistem digital, serta tata kelola operasional.</p>
        </section>
        <section class="public-card about-card">
            <span class="about-card-index">02 / LAYANAN</span>
            <h2>Layanan profesional</h2>
            <p>Layanan profesional Sentinal mencakup kajian risiko dan tata kelola, riset dan assessment, pemetaan dan analisis spasial, engineering dan integrasi sistem, pengembangan sistem digital, serta dukungan operasi dan pemeliharaan.</p>
        </section>
        <section class="public-card about-card">
            <span class="about-card-index">03 / PLATFORM</span>
            <h2>Sentinel EMP</h2>
            <p>Untuk mendukung implementasi dan pengelolaan sistem, Sentinal mengembangkan Sentinel EMP sebagai platform teknologi dan layanan digital untuk integrasi perangkat, data, visualisasi, evaluasi, notifikasi, dan operasi sistem monitoring.</p>
            <a class="about-contact" href="{{ route('contact-us') }}">Hubungi kami <span aria-hidden="true">↗</span></a>
        </section>
    </div>
</div>
@endsection
