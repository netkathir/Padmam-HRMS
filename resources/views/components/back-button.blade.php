{{--
    File: resources/views/components/back-button.blade.php
    Purpose: Reusable grey back-navigation button (top-right of card header)
    Author: System
    Date: 2026-06-30
--}}
@props(['href' => null, 'label' => 'Back'])

<a href="{{ $href ?? url()->previous() }}"
   class="btn btn-sm d-inline-flex align-items-center gap-1"
   style="background:#f3f4f6;border:1px solid #d1d5db;color:#374151;font-size:13px;font-weight:500;
          border-radius:6px;padding:5px 13px 5px 8px;text-decoration:none;transition:background .15s,border-color .15s;">
    <i class="bi bi-arrow-left-short" style="font-size:17px;line-height:1;"></i>
    {{ $label }}
</a>
