@if($access = session()->pull('student_access'))
    <section class="panel access-card" aria-labelledby="access-heading">
        <div class="panel-head"><h2 id="access-heading">Akses siswa siap dibagikan</h2><x-admin.icon name="check" /></div>
        <p>Salin akses untuk {{ $access['name'] }} dan bagikan secara pribadi kepada siswa atau wali. Kata sandi sementara ini hanya tampil sekali.</p>
        <label for="student-access-message">Pesan akses siswa</label>
        <textarea id="student-access-message" rows="4" readonly spellcheck="false">Halo, akses siswa Timore Soccer School untuk {{ $access['name'] }} sudah dibuat.
Username: {{ $access['username'] }}
Kata sandi sementara: {{ $access['password'] }}</textarea>
        <div class="access-actions"><button type="button" class="btn primary" data-copy-text="student-access-message">Salin akses siswa</button><span data-copy-status role="status"></span></div>
    </section>
@endif
