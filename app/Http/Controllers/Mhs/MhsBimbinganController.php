<?php
namespace App\Http\Controllers\mhs;

use App\Http\Controllers\Controller;
use App\Models\Bimbingan;
use App\Models\Dokumen;
use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MhsBimbinganController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:3');
    }

    public function index()
    {
        $user = Auth::user();

        if (!$user->mahasiswa) {
            return view('mhs.bimbingan.index', ['bimbingan' => collect()]);
        }

        $nim = $user->mahasiswa->id_nim;

        $bimbingan = Bimbingan::with(['dosen', 'dokumen', 'komentar'])
            ->where('nim', $nim)
            ->orderBy('created_at', 'desc') // Changed to order by created_at
            ->paginate(10);

        return view('mhs.bimbingan.index', compact('bimbingan'));
    }

    public function show($id)
    {
        $user = Auth::user();

        if (!$user->mahasiswa) {
            abort(403, 'Unauthorized action.');
        }

        $nim = $user->mahasiswa->id_nim;

        $bimbingan = Bimbingan::with([
            'dosen',
            'dokumen',
            'komentar' => function ($query) {
                $query->with(['pengirim:id_user,username'])
                    ->orderBy('waktu_kirim', 'asc');
            }
        ])
        ->where('nim', $nim)
        ->findOrFail($id);

        return view('mhs.bimbingan.show', compact('bimbingan'));
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user->mahasiswa) {
            abort(403, 'Unauthorized action.');
        }

        $dosen = Dosen::orderBy('nama', 'asc')->get();

        return view('mhs.bimbingan.create', compact('dosen'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->mahasiswa) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'nidn' => 'required|exists:dosens,id_nidn',
            'topik' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'tanggal' => 'required|date',
            'dokumen.*' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        $nim = $user->mahasiswa->id_nim;

        $bimbinganId = DB::table('bimbingan')->insertGetId([
            'nim' => $nim,
            'nidn' => $request->nidn,
            'topik' => $request->topik,
            'catatan' => $request->deskripsi,
            'tanggal' => $request->tanggal,
            'status_validasi' => 'Pending',
            'created_at' => now(), // Added created_at
            'updated_at' => now(), // Added updated_at
        ]);

        $bimbingan = (object)['id_bimbingan' => $bimbinganId];

        if ($request->hasFile('dokumen')) {
            foreach ($request->file('dokumen') as $file) {
                // Create directory if not exists
                if (!file_exists(public_path('doc'))) {
                    mkdir(public_path('doc'), 0777, true);
                }

                $fileName = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('doc'), $fileName);

                Dokumen::create([
                    'id_bimbingan' => $bimbingan->id_bimbingan,
                    'nama_file' => $file->getClientOriginalName(),
                    'file_path' => 'doc/'.$fileName,
                    'uploaded_at' => now(),
                    'created_at' => now(), // Added created_at
                    'updated_at' => now(), // Added updated_at
                ]);
            }
        }

        return redirect()->route('mhs.bimbingan.index')
            ->with('success', 'Bimbingan berhasil dibuat dan menunggu validasi dosen.');
    }

    public function edit($id)
    {
        $user = Auth::user();

        if (!$user->mahasiswa) {
            abort(403, 'Unauthorized action.');
        }

        $nim = $user->mahasiswa->id_nim;

        $bimbingan = Bimbingan::with(['dokumen', 'komentar.pengirim'])
            ->where('nim', $nim)
            ->where('status_validasi', 'Pending')
            ->findOrFail($id);

        $dosen = Dosen::orderBy('nama', 'asc')->get();

        return view('mhs.bimbingan.edit', compact('bimbingan', 'dosen'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->mahasiswa) {
            abort(403, 'Unauthorized action.');
        }

        $nim = $user->mahasiswa->id_nim;

        $request->validate([
            'topik' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'tanggal' => 'required|date',
            'dokumen.*' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        $bimbingan = Bimbingan::where('nim', $nim)
            ->where('status_validasi', 'Pending')
            ->findOrFail($id);

        DB::table('bimbingan')
            ->where('id_bimbingan', $id)
            ->where('nim', $nim)
            ->where('status_validasi', 'Pending')
            ->update([
                'topik' => $request->topik,
                'catatan' => $request->deskripsi,
                'tanggal' => $request->tanggal,
                'updated_at' => now(), // Added updated_at
            ]);

        if ($request->hasFile('dokumen')) {
            foreach ($request->file('dokumen') as $file) {
                // Create directory if not exists
                if (!file_exists(public_path('doc'))) {
                    mkdir(public_path('doc'), 0777, true);
                }

                $fileName = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('doc'), $fileName);

                Dokumen::create([
                    'id_bimbingan' => $bimbingan->id_bimbingan,
                    'nama_file' => $file->getClientOriginalName(),
                    'file_path' => 'doc/'.$fileName,
                    'uploaded_at' => now(),
                    'created_at' => now(), // Added created_at
                    'updated_at' => now(), // Added updated_at
                ]);
            }
        }

        return redirect()->route('mhs.bimbingan.index')
            ->with('success', 'Bimbingan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->mahasiswa) {
            abort(403, 'Unauthorized action.');
        }

        $nim = $user->mahasiswa->id_nim;

        $bimbingan = Bimbingan::where('nim', $nim)
            ->where('status_validasi', 'Pending')
            ->findOrFail($id);

        foreach ($bimbingan->dokumen as $dokumen) {
            if (file_exists(public_path($dokumen->file_path))) {
                unlink(public_path($dokumen->file_path));
            }
            $dokumen->delete();
        }

        $bimbingan->delete();

        return redirect()->route('mhs.bimbingan.index')
            ->with('success', 'Bimbingan berhasil dihapus.');
    }
}
