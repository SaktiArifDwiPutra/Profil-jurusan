<nav class="navbar navbar-expand-lg sticky-top bg-white shadow-sm">
  <div class="container">

    <img src="{{ asset('assets/logo/logorilll2.png') }}"
         alt="logo"
         style="height:75px; margin-right:8px;">

    <span class="navbar-brand fw-bold text-primary mt-2">
      ProfilJurusan
    </span>

    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-center">

        {{-- ================= BAGIAN TAMU (BELUM LOGIN) ================= --}}
        @guest
          <li class="nav-item">
            <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}"><i class="fa fa-home"></i> Beranda</a>
          </li>
          {{-- Menu Tamu Lainnya --}}
          <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Siswa</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Guru</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Kelas</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Mapel</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Nilai</a></li>
          
          <li class="nav-item ms-2">
            <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('login') }}">Login</a>
          </li>
        @endguest

        {{-- ================= BAGIAN USER LOGIN (ADMIN, GURU, SISWA) ================= --}}
        @auth
            {{-- 1. DASHBOARD (Untuk SEMUA Role) --}}
            <li class="nav-item">
              <a class="nav-link {{ request()->routeIs('dashboard') ? 'active text-primary fw-bold' : '' }}"
                 href="{{ route('dashboard') }}">
                <i class="fa fa-home"></i>Dashboard
              </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.siswa.*') ? 'active text-primary fw-bold' : '' }}"
                   href="{{ route('admin.siswa.index') }}">
                   Siswa
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.guru.*') ? 'active text-primary fw-bold' : '' }}"
                  href="{{ route('admin.guru.index') }}">
                  Guru
                </a>
            </li>

            <li class="nav-item">
              <a class="nav-link {{ request()->routeIs('admin.kelas.*') ? 'active text-primary fw-bold' : '' }}"
                 href="{{ route('admin.kelas.index') }}">
                 Kelas
              </a>
            </li>

            <li class="nav-item">
              <a class="nav-link {{ request()->routeIs('admin.mapel.*') ? 'active text-primary fw-bold' : '' }}"
               href="{{ route('admin.mapel.index') }}">
               Mapel
              </a>
            </li>

            <li class="nav-item">
              <a class="nav-link {{ request()->routeIs('admin.nilai.*') ? 'active text-primary fw-bold' : '' }}"
               href="{{ route('admin.nilai.index') }}">
               Nilai
              </a> 
            </li>       
            
            <li class="nav-item">
              <a class="nav-link {{ request()->routeIs('admin.absensi.*') ? 'active text-primary fw-bold' : '' }}"
               href="{{ route('admin.absensi.index') }}">
               Absen
              </a>
            </li>

            {{-- 3. MENU KHUSUS ADMIN --}}
            @if(auth()->user()->role === 'admin')
                <li class="nav-item">
                  <a class="nav-link {{ request()->routeIs('admin.akun.*') ? 'active text-primary fw-bold' : '' }}"
                   href="{{ route('admin.akun.index') }}">Akun</a>
                </li>
            @endif

            {{-- TOMBOL LOGOUT TRIGGER MODAL (Untuk SEMUA Role) --}}
            <li class="nav-item ms-3">
              <button type="button" class="btn btn-outline-danger btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#logoutModal">
                 Logout
              </button>
            </li>
        @endauth

      </ul>
    </div>
  </div>
</nav>

@auth
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      
    <div class="modal-header border-bottom-0 pb-0 justify-content-center mt-3">
        <h5 class="fw-bold m-0 text-dark" id="logoutModalLabel">Konfirmasi Logout</h5>
    </div>
      
      <div class="modal-body text-center py-4 px-4">
        <div class="mb-3 text-danger">
           <i class="fa fa-sign-out-alt" style="font-size: 3rem;"></i>
        </div>
        <p class="mb-0 text-muted">
          Apakah Anda yakin ingin keluar dari sistem?
        </p>
      </div>
      
      <div class="modal-footer border-top-0 pt-0 justify-content-center pb-4">
        <button type="button" class="btn btn-light px-4 rounded-pill border" data-bs-dismiss="modal">Batal</button>
        
        <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
          @csrf
          <button type="submit" class="btn btn-danger px-4 rounded-pill shadow-sm">Ya, Keluar</button>
        </form>
      </div>

    </div>
  </div>
</div>
@endauth