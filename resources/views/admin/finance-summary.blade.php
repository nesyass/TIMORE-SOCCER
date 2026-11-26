<section aria-label="Ringkasan keuangan">
    <form class="inline-filter finance-period" action="{{ route('admin.index', $section) }}">
        <label for="finance-month">Bulan pemasukan</label>
        <input type="month" id="finance-month" name="month" value="{{ $finance['month'] }}" required>
        <button class="btn" type="submit">Terapkan</button>
    </form>
    <div class="stats">
        @foreach ([
            ['wallet', 'Saldo kas', 'Rp '.\Illuminate\Support\Number::format((float)$finance['balance'], maxPrecision: 2, locale: 'id'), 'Seluruh transaksi tercatat'],
            ['chart', 'Pemasukan bulan terpilih', 'Rp '.\Illuminate\Support\Number::format((float)$finance['income'], maxPrecision: 2, locale: 'id'), 'Penerimaan yang tercatat'],
            ['file', 'Iuran belum lunas', $finance['unpaid'], 'Jumlah catatan iuran'],
            ['users', 'Siswa aktif', $finance['students'], 'Siswa dengan status aktif'],
        ] as [$icon, $label, $value, $hint])
            <div class="stat"><span class="stat-label"><span class="stat-icon"><x-admin.icon :name="$icon" /></span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $hint }}</small></div>
        @endforeach
    </div>
    <nav class="tabs" aria-label="Keuangan">
        @foreach (['cashbook' => 'Buku kas', 'dues' => 'Iuran'] as $slug => $label)
            <a href="{{ route('admin.index', ['section' => $slug, 'month' => $finance['month']]) }}" @if($section === $slug) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
</section>
