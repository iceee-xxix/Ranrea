@extends('layouts.luxury-nav')

@section('title', 'หน้ารายละเอียด')

@section('content')
<?php

use App\Models\Config;

$config = Config::first();
?>

<style>
    .title-buy {
        font-size: 30px;
        font-weight: bold;
        color: <?= $config->color_font != '' ? $config->color_font : '#ffffff' ?>;
        margin-bottom: 1rem;
    }

    .card-order {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease;
    }

    .card-order:hover {
        transform: scale(1.01);
    }

    .badge-status {
        font-size: 0.875rem;
        padding: 0.4em 0.75em;
        border-radius: 0.75rem;
    }
</style>

<div class="container py-4">
    <div class="title-buy text-center">📋 รายละเอียดคำสั่งออเดอร์</div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white fw-bold">
            <i class="bi bi-receipt-cutoff me-1"></i> ออเดอร์ของคุณ
        </div>
        <div class="card-body">
            @forelse($orders as $rs)
            <div class="card card-order mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-8">
                            <h5 class="fw-bold mb-2">🧾 เลขออเดอร์ #{{ $rs->id }}</h5>
                            <p class="mb-1">
                                <span class="fw-bold">สถานะ:</span>
                                @php
                                $statusText = match($rs->status) {
                                1 => 'กำลังทำอาหาร',
                                2 => 'ออเดอร์สำเร็จแล้ว',
                                3 => 'จัดส่งสำเร็จ',
                                default => 'ไม่ทราบสถานะ',
                                };

                                $statusClass = match($rs->status) {
                                1 => 'bg-warning text-dark',
                                2 => 'bg-success',
                                3 => 'bg-primary',
                                default => 'bg-secondary',
                                };
                                @endphp
                                <span class="badge badge-status {{ $statusClass }}">{{ $statusText }}</span>
                            </p>
                            <small class="text-muted">ราคา: {{ number_format($rs->total ?? 0, 2) }} บาท</small>
                        </div>
                        <div class="col-12 col-md-4 text-start text-md-end mt-3 mt-md-0">
                            <button data-id="{{ $rs->id }}" class="btn btn-outline-primary btn-sm modalShow w-100 w-md-auto">
                                <i class="bi bi-eye"></i> ดูรายละเอียด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">
                <i class="bi bi-exclamation-circle"></i> ไม่มีรายการอาหารที่สั่ง
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" tabindex="-1" id="modal-detail">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle me-1"></i> รายละเอียดออเดอร์</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="body-html">
                <!-- Content via AJAX -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Script -->
<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).on('click', '.modalShow', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({
            type: 'POST',
            url: '{{ route("listOrderDetailMain") }}',
            data: {
                id: id
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#modal-detail').modal('show');
                $('#body-html').html(response);
            }
        });
    });
</script>
@endsection