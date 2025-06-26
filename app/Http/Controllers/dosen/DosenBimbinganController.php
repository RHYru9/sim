<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Bimbingan;
use App\Models\Dokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DosenBimbinganController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:2'); // Hanya dosen
    }

    public function index()
    {
        $user = Auth::user();

        if (!$user->dosen) {
            return view('dosen.bimbingan.index', ['bimbingan' => collect()]);
        }

        $nidn = $user->dosen->id_nidn;

        $bimbingan = Bimbingan::with(['mahasiswa', 'dokumen', 'komentar'])
            ->where('nidn', $nidn)
            ->orderBy('tanggal', 'desc')
            ->paginate(10);

        return view('dosen.bimbingan.index', compact('bimbingan'));
    }

    public function show($id)
    {
        $user = Auth::user();

        if (!$user->dosen) {
            abort(403, 'Unauthorized action.');
        }

        $nidn = $user->dosen->id_nidn;

        $bimbingan = Bimbingan::with([
                'mahasiswa',
                'dokumen',
                'komentar' => function ($query) {
                    $query->with(['pengirim:id_user,username'])
                    ->orderBy('waktu_kirim', 'asc');
                }
            ])
            ->where('nidn', $nidn)
            ->findOrFail($id);

        return view('dosen.bimbingan.show', compact('bimbingan'));
    }

    public function edit($id)
    {
        $user = Auth::user();

        if (!$user->dosen) {
            abort(403, 'Unauthorized action.');
        }

        $nidn = $user->dosen->id_nidn;

        $bimbingan = Bimbingan::with(['dokumen', 'komentar.pengirim'])
            ->where('nidn', $nidn)
            ->findOrFail($id);

        return view('dosen.bimbingan.edit', compact('bimbingan'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->dosen) {
            abort(403, 'Unauthorized action.');
        }

        $nidn = $user->dosen->id_nidn;

        $request->validate([
            'catatan' => 'nullable|string',
            'status_validasi' => 'required|in:Valid,Invalid,Pending',
            'dokumen.*' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        $bimbingan = Bimbingan::where('nidn', $nidn)->findOrFail($id);

        $bimbingan->update([
            'catatan' => $request->catatan,
            'status_validasi' => $request->status_validasi,
        ]);

        if ($request->hasFile('dokumen')) {
            foreach ($request->file('dokumen') as $file) {
                $path = $file->store('dokumen_bimbingan', 'public');

                Dokumen::create([
                    'id_bimbingan' => $bimbingan->id_bimbingan,
                    'nama_file' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'uploaded_at' => now(),
                ]);
            }
        }

        return redirect()->route('dosen.bimbingan.index')
            ->with('success', 'Bimbingan berhasil diperbarui.');
    }
}
