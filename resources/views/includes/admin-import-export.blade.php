{{--
    Tombol Export + Import Excel (dengan popup upload) untuk halaman daftar admin.

    Pemakaian (taruh di dalam div tombol header, sebelum tombol "Tambah ..."):
        @include('includes.admin-import-export', [
            'judul'         => 'Master Logistik',
            'keterangan'    => 'Item yang sudah ada akan diperbarui, bukan dibuat ganda.',
            'templateRoute' => route('admin.logistik.import.template'),
            'previewRoute'  => route('admin.logistik.import.preview'),
            'exportRoute'   => route('admin.logistik.export'),   // null = tombol export tidak ditampilkan
            'exportCsvRoute'=> route('admin.logistik.export.csv'), // opsional: tombol Export CSV (untuk aplikasi offline)
            'templateFile'  => 'template_import_logistik.xlsx',
        ])
--}}
<div class="inline-flex flex-wrap items-center gap-3"
     x-data="{ importOpen: {{ $errors->has('file') ? 'true' : 'false' }} }">

    @if (! empty($exportRoute))
        <a href="{{ $exportRoute }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-bold px-4 py-3 rounded-xl shadow-sm border border-slate-200">
            <i class="fas fa-file-export text-sm text-emerald-600"></i> Export
        </a>
    @endif

    @if (! empty($exportCsvRoute))
        <a href="{{ $exportCsvRoute }}" title="File CSV untuk menu Master Barang &rarr; Import di aplikasi offline"
           class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-bold px-4 py-3 rounded-xl shadow-sm border border-slate-200">
            <i class="fas fa-file-csv text-sm text-emerald-600"></i> Export CSV (Aplikasi)
        </a>
    @endif

    <button type="button" @click="importOpen = true" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-bold px-4 py-3 rounded-xl shadow-sm border border-slate-200">
        <i class="fas fa-file-import text-sm text-blue-600"></i> Import Excel
    </button>

    <!-- Popup dipindahkan ke <body> agar tidak terpotong & selalu di atas topbar -->
    <template x-teleport="body">
        <div x-show="importOpen" x-cloak style="display: none;"
             @keydown.escape.window="importOpen = false"
             class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="importOpen = false"></div>

            <div class="relative min-h-full flex items-center justify-center p-4">
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 md:p-8">
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-800">Import {{ $judul }}</h3>
                            <p class="text-sm text-slate-500 mt-1">Tambah banyak data sekaligus dari file Excel.</p>
                        </div>
                        <button type="button" @click="importOpen = false" class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <ol class="text-sm text-slate-600 space-y-2 mb-5 list-decimal list-inside">
                        <li>
                            Download template:
                            <a href="{{ $templateRoute }}" class="font-bold text-blue-600 hover:underline">
                                <i class="fas fa-download text-xs"></i> {{ $templateFile ?? 'template.xlsx' }}
                            </a>
                        </li>
                        <li>Isi data di sheet <b>"Isi Data"</b>. {{ $keterangan ?? '' }}</li>
                        <li>Upload file, periksa pratinjau, lalu konfirmasi.</li>
                    </ol>

                    <form method="POST" action="{{ $previewRoute }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                               class="block w-full text-sm text-slate-600 border border-slate-200 rounded-xl cursor-pointer
                                      file:mr-4 file:py-2.5 file:px-4 file:border-0 file:bg-blue-50 file:text-blue-700 file:font-bold hover:file:bg-blue-100">
                        <p class="text-xs text-slate-400 mt-2">Format .xlsx, .xls, atau .csv · maks. 5 MB</p>

                        @error('file')
                            <p class="text-sm text-rose-600 mt-2">{{ $message }}</p>
                        @enderror

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="importOpen = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold">
                                <i class="fas fa-eye text-sm"></i> Lihat Pratinjau
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
