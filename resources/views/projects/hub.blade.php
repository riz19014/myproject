@extends('layouts.app')

@section('title', $project->name)

@push('head')
<style>
    .project-hub-hero {
        background: linear-gradient(135deg, #fff7ed 0%, #ffffff 48%, #f8fafc 100%);
        border: 1px solid rgba(249, 115, 22, 0.18);
        border-radius: 16px;
        padding: 2rem 2rem 1.75rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(15, 23, 42, 0.06);
    }
    .project-hub-hero h1 {
        font-size: clamp(1.5rem, 4vw, 2rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        margin-bottom: 0.35rem;
    }
    .project-hub-hero .project-hub-meta {
        color: var(--text-muted);
        font-size: 0.95rem;
    }
    .project-hub-link {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
        height: 100%;
        padding: 1.5rem 1.35rem;
        border-radius: 14px;
        border: 1px solid var(--border-dark);
        background: var(--card-bg);
        text-decoration: none;
        color: inherit;
        transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
    }
    .project-hub-link:hover {
        border-color: rgba(249, 115, 22, 0.45);
        box-shadow: 0 12px 28px rgba(249, 115, 22, 0.12);
        transform: translateY(-2px);
        color: inherit;
    }
    .project-hub-link__icon {
        width: 3rem;
        height: 3rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        margin-bottom: 1rem;
    }
    .project-hub-link__icon--purchase {
        background: rgba(34, 197, 94, 0.12);
        color: #15803d;
    }
    .project-hub-link__icon--sale {
        background: rgba(59, 130, 246, 0.12);
        color: #1d4ed8;
    }
    .project-hub-link__icon--files {
        background: rgba(249, 115, 22, 0.14);
        color: #ea580c;
    }
    .project-hub-link__title {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
    }
    .project-hub-link__desc {
        font-size: 0.875rem;
        color: var(--text-muted);
        line-height: 1.45;
        margin: 0;
    }
    .project-hub-link__arrow {
        margin-top: auto;
        padding-top: 1rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--accent-orange);
    }
</style>
@endpush

@section('content')
<div class="mb-3">
    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-theme">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>All projects
    </a>
</div>

<header class="project-hub-hero text-center text-md-start">
    <h1 class="mb-0">{{ $project->name }}</h1>
    @if($project->landType)
        <p class="project-hub-meta mb-0 mt-1">
            <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $project->landType->name }}
        </p>
    @endif
</header>

<div class="row g-3 g-lg-4 mb-4">
    <div class="col-md-4">
        <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}" class="project-hub-link">
            <span class="project-hub-link__icon project-hub-link__icon--purchase" aria-hidden="true">
                <i class="bi bi-bag-check"></i>
            </span>
            <span class="project-hub-link__title">Purchase land</span>
            <p class="project-hub-link__desc">Purchase files for this project — sellers, land areas, and documents.</p>
            <span class="project-hub-link__arrow">Open <i class="bi bi-chevron-right" aria-hidden="true"></i></span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('projects.sale-land', $project) }}" class="project-hub-link">
            <span class="project-hub-link__icon project-hub-link__icon--sale" aria-hidden="true">
                <i class="bi bi-signpost-split"></i>
            </span>
            <span class="project-hub-link__title">Sale land</span>
            <p class="project-hub-link__desc">Project-level land sales, parties, buyers, and land cuttings.</p>
            <span class="project-hub-link__arrow">Open <i class="bi bi-chevron-right" aria-hidden="true"></i></span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('sale.files.index', $project) }}" class="project-hub-link">
            <span class="project-hub-link__icon project-hub-link__icon--files" aria-hidden="true">
                <i class="bi bi-files"></i>
            </span>
            <span class="project-hub-link__title">Files sale</span>
            <p class="project-hub-link__desc">Sale files for this project — direct plot sales and exemption calculator.</p>
            <span class="project-hub-link__arrow">Open <i class="bi bi-chevron-right" aria-hidden="true"></i></span>
        </a>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
    <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-theme btn-sm">Edit project</a>
    <a href="{{ route('projects.ledger.pdf', $project) }}" class="btn btn-outline-theme btn-sm">Ledger PDF</a>
</div>
@endsection
