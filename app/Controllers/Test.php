<?php

namespace App\Controllers;

use App\Models\QuestionModel;
use App\Models\UserJawabanModel;
use App\Models\KeywordModel;
use App\Models\TestResultModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class Test extends Controller
{
    use ResponseTrait;

    protected $questionModel;
    protected $userJawabanModel;
    protected $keywordModel;
    protected $testResultModel;

    public function __construct()
    {
        // Pastikan Anda memuat helper 'session' jika Anda mengandalkan sesi
        helper('session');

        $this->questionModel    = new QuestionModel();
        $this->userJawabanModel = new UserJawabanModel();
        $this->keywordModel     = new KeywordModel();
        $this->testResultModel  = new TestResultModel();
    }

    /**
     * Endpoint untuk memulai tes dan mengambil soal pertama.
     * Diasumsikan user sudah login dan id_user serta id_test bisa didapatkan.
     *
     * @param int|null $id_test ID Tes yang akan dimulai.
     * @return \CodeIgniter\HTTP\ResponseInterface
     */

    private function getUserFromToken()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null; // Atau return response failUnauthorized
        }

        $token = $matches[1];
        $key = getenv('JWT_SECRET'); // Ambil dari .env, misalnya: JWT_SECRET="rahasia123"

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return $decoded; // return user info dari token
        } catch (\Exception $e) {
            return null; // Token tidak valid
        }
    }

    public function startTest($id_test = null)
    {
        // 1. OTENTIKASI PENGGUNA (DARI TOKEN JWT)
        /** @var \CodeIgniter\HTTP\IncomingRequest $request */
        $request = $this->request; // Pastikan $this->request tersedia
        $decodedToken = $this->getUserFromToken();
        if (!$decodedToken) {
            return $this->failUnauthorized('Token tidak valid atau tidak ditemukan.');
        }
        $id_user = $decodedToken->uid;

        // 2. VALIDASI id_test DARI URL
        if (is_null($id_test) || !is_numeric($id_test)) {
            return $this->fail('ID Tes tidak valid atau tidak disertakan.', 400);
        }

        // 3. CEK DATA DI TABEL test_results UNTUK USER DAN TES INI
        $existingResult = $this->testResultModel
            ->where('id_user', $id_user)
            ->where('id_test', $id_test)
            ->first();

        // 4. PENGECEKAN KONDISI BERDASARKAN DATA DI DATABASE
        if (!$existingResult) {
            // Jika tidak ada record sama sekali
            // Mengirimkan informasi debug tambahan di respons JSON
            return $this->fail(
                [
                    // Pesan error asli
                    'error' => 'Sesi tes untuk Anda belum diinisialisasi. Mohon lengkapi data awal tes terlebih dahulu.',
                    // Informasi debug tambahan
                    'debug_info' => [
                        'searched_id_user' => $id_user,
                        'searched_id_test' => $id_test,
                        'database_result' => 'Tidak ada record ditemukan untuk kombinasi user & tes ini.'
                    ]
                ],
                400 // Status code HTTP
            );
        }

        // Jika record ditemukan, sekarang cek apakah kolom pendidikan dan tujuan sudah terisi.
        if (empty($existingResult['pendidikan']) || empty($existingResult['tujuan_Pemeriksaan'])) {
            // Record ada, tapi data pendidikan atau tujuan_Pemeriksaan di database kosong.
            // Mengirimkan informasi debug tambahan di respons JSON
            return $this->fail(
                [
                    // Pesan error asli
                    'error' => 'Data Pendidikan dan/atau Tujuan Pemeriksaan untuk sesi tes ini belum lengkap. Mohon lengkapi data tersebut terlebih dahulu.',
                    // Informasi debug tambahan
                    'debug_info' => [
                        'retrieved_test_result_id' => $existingResult['id_result'] ?? 'ID Result tidak tersedia', // Ganti 'id_result' dengan nama primary key Anda jika berbeda
                        'retrieved_pendidikan' => $existingResult['pendidikan'],
                        'retrieved_tujuan_Pemeriksaan' => $existingResult['tujuan_Pemeriksaan'],
                        'full_existing_result_from_db' => $existingResult // Seluruh data yang diambil dari DB
                    ]
                ],
                400 // Status code HTTP
            );
        }

        // 5. JIKA SEMUA DATA DI DATABASE SUDAH LENGKAP, LANJUTKAN TES (Kode dari sini tetap sama)
        $currentTestResultId = $existingResult['id_result']; // Gunakan ID dari record yang ada di database

        // AMBIL SOAL
        $questions = $this->questionModel->where('id_test', $id_test)->findAll();
        if (empty($questions)) {
            return $this->failNotFound('Soal tes tidak ditemukan untuk ID tes ini.');
        }
        shuffle($questions);

        // SIMPAN STATE TES KE SESI
        session()->set('current_test_questions', $questions);
        session()->set('current_question_index', 0);
        session()->set('current_test_result_id', $currentTestResultId);
        session()->set('current_test_id', $id_test); // Tambahan penting


        // KIRIM SOAL PERTAMA
        $firstQuestion = $questions[0];
        return $this->respond([
            'status'  => 200,
            'message' => 'Validasi sesi tes berhasil. Soal pertama berhasil diambil.',
            'data'    => [
                'question_number' => 1,
                'total_questions' => count($questions),
                'question'        => [
                    'id_question'   => $firstQuestion['id_question'],
                    'pasangan_kata' => $firstQuestion['pasangan_kata']
                ],
            ]
        ]);
    }

    /**
     * Endpoint untuk submit jawaban soal saat ini dan mengambil soal berikutnya.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function submitAnswerAndGetNextQuestion()
    {

        // ===== BAGIAN BARU YANG PERLU DITAMBAHKAN =====
        // 1. OTENTIKASI PENGGUNA (DARI TOKEN JWT)
        /** @var \CodeIgniter\HTTP\IncomingRequest $request */

        $request = $this->request;
        $decodedToken = $this->getUserFromToken();
        if (!$decodedToken) {
            return $this->failUnauthorized('Token tidak valid atau tidak ditemukan.');
        }
        $id_user = $decodedToken->uid; // <-- AMBIL ID USER DARI SINI
        // =============================================


        // Fokus pada data sesi inti yang diset oleh startTest
        $questions       = session()->get('current_test_questions');
        $currentIndex    = session()->get('current_question_index');
        $id_result  = session()->get('current_test_result_id');

        // Kondisi if Anda sekarang menjadi:
        if (is_null($id_result) || is_null($questions) || is_null($currentIndex)) {
            return $this->failUnauthorized('Sesi tes tidak ditemukan atau kadaluarsa. Mohon mulai tes kembali.');
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $id_question_submitted = $data['id_question'] ?? null;
        $jawaban_user          = $data['jawaban_user'] ?? null;


        // Pastikan soal yang disubmit adalah soal yang sedang aktif
        $currentQuestion = $questions[$currentIndex];
        if (empty($id_question_submitted) || (int)$id_question_submitted !== (int)$currentQuestion['id_question']) {
            return $this->fail(['message' => 'ID Soal tidak valid atau tidak sesuai dengan soal yang sedang aktif.'], 400);
        }
        if (empty($jawaban_user)) {
            return $this->fail(['message' => 'Jawaban tidak boleh kosong.']);
        }

        $jawabanData = [
            'id_result' => $id_result, // <--- Ini kunci penghubung ke test_results
            'id_question'    => (int)$currentQuestion['id_question'],
            'jawaban_user'   => $jawaban_user,
            'waktu_submit'   => date('Y-m-d H:i:s'),
        ];

        // Validasi dan simpan jawaban
        if (!$this->userJawabanModel->save($jawabanData)) {
            return $this->fail('Gagal menyimpan jawaban: ' . json_encode($this->userJawabanModel->errors()), 500);
        }

        $nextIndex = $currentIndex + 1;
        session()->set('current_question_index', $nextIndex);

        if ($nextIndex < count($questions)) {
            // Ada soal berikutnya, kirimkan
            $nextQuestion = $questions[$nextIndex];
            return $this->respond([
                'status'          => 200,
                'message'         => 'Jawaban tersimpan. Soal berikutnya berhasil diambil.',
                'question_number' => $nextIndex + 1,
                'total_questions' => count($questions),
                'question'        => [
                    'id_question'   => $nextQuestion['id_question'],
                    'pasangan_kata' => $nextQuestion['pasangan_kata']
                ],
            ]);
        } else {
            // Semua soal sudah selesai, panggil fungsi untuk menghitung dan menampilkan hasil
            $id_test = session()->get('current_test_id');
            if (is_null($id_test)) {
                return $this->failUnauthorized('ID Tes tidak ditemukan dalam sesi. Mohon mulai tes kembali.');
            }
            return $this->getResult($id_user, $id_test);
        }
    }

    /**
     * Endpoint untuk menghitung dan menampilkan hasil tes.
     * Dapat dipanggil setelah soal terakhir disubmit, atau secara terpisah.
     *
     * @param int|null $userId ID user.
     * @param int|null $testId ID Tes.
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getResult($userId = null, $testId = null)
    {
        // 1. Validasi parameter input
        if (!$userId || !$testId) {
            return $this->fail('ID User dan ID Test harus disertakan.', 400);
        }

        // 2. HANYA FOKUS MENCARI DATA YANG SUDAH ADA DI DATABASE
        // Tugasnya sekarang sangat sederhana.
        $result = $this->testResultModel
            ->where('id_user', $userId)
            ->where('id_test', $testId)
            ->first();

        // 3. Jika data tidak ditemukan, kirim pesan error
        if (!$result) {
            return $this->failNotFound('Hasil tes tidak ditemukan untuk user dan tes ini.');
        }

        // 4. Bersihkan sesi tes setelah hasil ditampilkan (opsional)
        // Anda bisa memindahkan ini ke tempat lain jika perlu, misal saat user logout.
        session()->remove([
            'current_test_questions',
            'current_question_index',
            'current_test_id',
            'current_user_id',
            'current_test_result_id'
        ]);

        // 5. Kembalikan data hasil yang ditemukan apa adanya
        return $this->respond([
            'status'  => 200,
            'message' => 'Hasil tes Anda:',
            'data'    => $result // Langsung kirim seluruh objek $result
        ]);
    }

    /**
     * Logika perhitungan skor.
     * @param int $userId
     * @param int $testId
     * @return int total score
     */
    protected function calculateScore(int $userId, int $testId): int
    {
        $totalScore = 0;
        $userAnswers = $this->userJawabanModel->where(['id_user' => $userId])->findAll(); // Ambil semua jawaban user

        foreach ($userAnswers as $answer) {
            // Pastikan jawaban berasal dari tes yang relevan
            $question = $this->questionModel->find($answer['id_question']);
            if ($question && (int)$question['id_test'] === (int)$testId) {
                $keywords = $this->keywordModel->where('id_question', $answer['id_question'])->findAll();

                foreach ($keywords as $keyword) {
                    if (stripos($answer['jawaban_user'], $keyword['kata_kunci']) !== false) {
                        $totalScore += $keyword['skor'];
                    }
                }
            }
        }
        return $totalScore;
    }

    // Fungsi ini sudah siap untuk PATCH, tidak perlu diubah sama sekali.

    public function updateTestDetails($id_test_result = null)
    {
        // 1. Validasi ID dari URL
        if (is_null($id_test_result)) {
            return $this->fail('ID Hasil Tes harus disertakan.', 400);
        }

        // 2. Otentikasi & Otorisasi
        // ... (logika otorisasi tetap sama) ...
        // 2. Otentikasi & Otorisasi
        $user = $this->getUserFromToken(); // ← tambahkan ini

        if (!$user) {
            return $this->failUnauthorized('Token tidak valid atau tidak ada.');
        }

        $loggedInUserId = $user->uid; // ← bukan $request->user, tapi dari token yang sudah didecode

        // 3. Ambil data baru dari input
        $newData = $this->request->getJSON(true);

        // 4. Siapkan data yang akan di-update (ini adalah logika PATCH)
        $updateData = [];
        if (isset($newData['pendidikan'])) { // `isset` lebih aman daripada `!empty` jika ingin mengizinkan string kosong
            $updateData['pendidikan'] = $newData['pendidikan'];
        }
        if (isset($newData['tujuan_Pemeriksaan'])) {
            $updateData['tujuan_Pemeriksaan'] = $newData['tujuan_Pemeriksaan'];
        }

        if (empty($updateData)) {
            return $this->fail('Tidak ada data yang dikirim untuk di-update.', 400);
        }

        // 5. Lakukan UPDATE ke database
        if ($this->testResultModel->update($id_test_result, $updateData)) {
            return $this->respondUpdated([
                'status'  => 200,
                'message' => 'Data tes berhasil diperbarui.',
                'data'    => $this->testResultModel->find($id_test_result)
            ]);
        } else {
            return $this->fail($this->testResultModel->errors());
        }
    }

    /**
     * Logika untuk menentukan interpretasi berdasarkan skor.
     * @param int $score
     * @return string Interpretasi
     */
    protected function getInterpretation(int $score): string
    {
        if ($score >= 80) {
            return 'Anda memiliki potensi psikologi yang sangat baik.';
        } elseif ($score >= 60) {
            return 'Anda memiliki potensi psikologi yang baik.';
        } elseif ($score >= 40) {
            return 'Potensi psikologi Anda cukup baik, ada ruang untuk pengembangan.';
        } else {
            return 'Disarankan untuk melakukan eksplorasi lebih lanjut tentang potensi psikologi Anda.';
        }
    }
}
