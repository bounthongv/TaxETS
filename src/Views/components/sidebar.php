<?php
// src/Views/components/sidebar.php
?>

<div class="d-flex flex-column flex-shrink-0 p-3 bg-light">
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
        <span class="fs-4">TaxETS</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="#" class="nav-link link-dark active" aria-current="page">
                <i class="bi bi-speedometer2 me-2"></i>
                Dashboard
            </a>
        </li>
        <li>
            <a href="#" class="nav-link link-dark">
                <i class="bi bi-gear me-2"></i>
                System
            </a>
        </li>
        <li>
            <a href="#dataDictionaryCollapse" class="nav-link link-dark" data-bs-toggle="collapse" aria-expanded="false">
                <i class="bi bi-book me-2"></i>
                Data Dictionary
                <i class="bi bi-chevron-down float-end"></i>
            </a>
            <div class="collapse" id="dataDictionaryCollapse">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                    <li><a href="#" class="link-dark rounded"><i class="bi bi-dot me-2"></i>Terms</a></li>
                    <li><a href="#" class="link-dark rounded"><i class="bi bi-dot me-2"></i>Definitions</a></li>
                </ul>
            </div>
        </li>
        <li>
            <a href="#" class="nav-link link-dark">
                <i class="bi bi-calculator me-2"></i>
                Benchmark
            </a>
        </li>
        <li>
            <a href="#" class="nav-link link-dark">
                <i class="bi bi-database me-2"></i>
                Repository
            </a>
        </li>
        <li>
            <a href="#getTaxDataCollapse" class="nav-link link-dark" data-bs-toggle="collapse" aria-expanded="false">
                <i class="bi bi-file-earmark-excel me-2"></i>
                Get Tax Data by Import from Excel
                <i class="bi bi-chevron-down float-end"></i>
            </a>
            <div class="collapse" id="getTaxDataCollapse">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                    <li><a href="import_profit_tax.php" class="link-dark rounded"><i class="bi bi-dot me-2"></i>Import Data</a></li>
                    <li><a href="#" class="link-dark rounded"><i class="bi bi-dot me-2"></i>Manage Imports</a></li>
                </ul>
            </div>
        </li>
        <li>
            <a href="#" class="nav-link link-dark">
                <i class="bi bi-journal-text me-2"></i>
                Data Requirements to identify Repository
            </a>
        </li>
        <li>
            <a href="#" class="nav-link link-dark">
                <i class="bi bi-journal-check me-2"></i>
                Data Requirements to estimate TE
            </a>
        </li>
        <li>
            <a href="#" class="nav-link link-dark">
                <i class="bi bi-play-circle me-2"></i>
                Run Tax Expenditure
            </a>
        </li>
        <li>
            <a href="#reportCollapse" class="nav-link link-dark" data-bs-toggle="collapse" aria-expanded="false">
                <i class="bi bi-file-earmark-bar-graph me-2"></i>
                Report
                <i class="bi bi-chevron-down float-end"></i>
            </a>
            <div class="collapse" id="reportCollapse">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                    <li><a href="#" class="link-dark rounded"><i class="bi bi-dot me-2"></i>Summary Report</a></li>
                    <li><a href="#" class="link-dark rounded"><i class="bi bi-dot me-2"></i>Detailed Report</a></li>
                </ul>
            </div>
        </li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://github.com/twbs.png" alt="" width="32" height="32" class="rounded-circle me-2">
            <strong>User</strong>
        </a>
        <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser2">
            <li><a class="dropdown-item" href="#">New project...</a></li>
            <li><a class="dropdown-item" href="#">Settings</a></li>
            <li><a class="dropdown-item" href="#">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#">Sign out</a></li>
        </ul>
    </div>
</div>
