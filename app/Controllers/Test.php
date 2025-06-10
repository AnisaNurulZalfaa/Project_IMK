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
        helper('session');

        $this->questionModel    = new QuestionModel();
        $this->userJawabanModel = new UserJawabanModel();
        $this->keywordModel     = new KeywordModel();
        $this->testResultModel  = new TestResultModel();
    }

    private function getUserFromToken()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null; 
        }

        $token = $matches[1];
        $key = getenv('JWT_SECRET');

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return $decoded; 
        } catch (\Exception $e) {
            return null; 
        }
    }

    public function startTest($id_test = null)
    {
        $request = $this->request; 
        $decodedToken = $this->getUserFromToken();
        if (!$decodedToken) {
            return $this->failUnauthorized('Token tidak valid atau tidak ditemukan.');
        }
        $id_user = $decodedToken->uid;

        if (is_null($id_test) || !is_numeric($id_test)) {
            return $this->fail('ID Tes tidak valid atau tidak disertakan.', 400);
        }

        $existingResult = $this->testResultModel
            ->where('id_user', $id_user)
            ->where('id_test', $id_test)
            ->first();

        if (!$existingResult) {
            return $this->fail(
                [
                    'error' => 'Sesi tes untuk Anda belum diinisialisasi. Mohon lengkapi data awal tes terlebih dahulu.',
                    'debug_info' => [
                        'searched_id_user' => $id_user,
                        'searched_id_test' => $id_test,
                        'database_result' => 'Tidak ada record ditemukan untuk kombinasi user & tes ini.'
                    ]
                ],
                
            );
        }

        if (empty($existingResult['pendidikan']) || empty($existingResult['tujuan_Pemeriksaan'])) {
            return $this->fail(
                [
                    'error' => 'Data Pendidikan dan/atau Tujuan Pemeriksaan untuk sesi tes ini belum lengkap. Mohon lengkapi data tersebut terlebih dahulu.',
                    'debug_info' => [
                        'retrieved_test_result_id' => $existingResult['id_result'] ?? 'ID Result tidak tersedia', 
                        'retrieved_pendidikan' => $existingResult['pendidikan'],
                        'retrieved_tujuan_Pemeriksaan' => $existingResult['tujuan_Pemeriksaan'],
                        'full_existing_result_from_db' => $existingResult 
                    ]
                ],
               
            );
        }

        $currentTestResultId = $existingResult['id_result']; 

        $questions = $this->questionModel->where('id_test', $id_test)->findAll();
        if (empty($questions)) {
            return $this->failNotFound('Soal tes tidak ditemukan untuk ID tes ini.');
        }
        shuffle($questions);

        session()->set('current_test_questions', $questions);
        session()->set('current_question_index', 0);
        session()->set('current_test_result_id', $currentTestResultId);
        session()->set('current_test_id', $id_test); 

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

    public function submitAnswerAndGetNextQuestion()
    {
        $decodedToken = $this->getUserFromToken();
        if (!$decodedToken) {
            return $this->failUnauthorized('Token tidak valid atau tidak ditemukan.');
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $id_question     = $data['id_question'] ?? null;
        $id_user         = $data['id_user'] ?? null;
        $id_test         = $data['id_test'] ?? null;
        $id_result       = $data['id_result'] ?? null;
        $jawaban_user    = $data['jawaban_user'] ?? null;

        if (!$id_question || !$id_user || !$id_test || !$id_result || !$jawaban_user) {
            return $this->failNotFound('Semua field (id_question, id_user, id_test, id_result, jawaban_user) wajib diisi.');
        }

        if ((int)$id_user !== (int)$decodedToken->uid) {
            return $this->failForbidden('ID user tidak sesuai dengan token.');
        }

        $existing = $this->userJawabanModel
            ->where([
                'id_question' => $id_question,
                'id_user'     => $id_user,
                'id_test'     => $id_test,
                'id_result'   => $id_result,
            ])->first();

        if ($existing) {
            if ($existing['jawaban_user'] !== $jawaban_user) {
                $updateData = [
                    'id_jawaban'            => $existing['id_jawaban'],
                    'jawaban_user'  => $jawaban_user,
                    'waktu_submit'  => date('Y-m-d H:i:s'),
                ];
                $this->userJawabanModel->save($updateData);
                return $this->respond([
                    'status'  => 200,
                    'message' => 'Jawaban diperbarui.',
                ]);
            } else {
                return $this->respond([
                    'status'  => 200,
                    'message' => 'Jawaban berhasil disimpan',
                ]);
            }
        }

        // Jika belum ada, simpan jawaban baru
        $newData = [
            'id_question'   => $id_question,
            'id_user'       => $id_user,
            'id_test'       => $id_test,
            'id_result'     => $id_result,
            'jawaban_user'  => $jawaban_user,
            'waktu_submit'  => date('Y-m-d H:i:s'),
        ];

        if ($this->userJawabanModel->insert($newData)) {
            return $this->respond([
                'status'  => 201,
                'message' => 'Jawaban baru berhasil disimpan.',
            ]);
        } else {
            return $this->fail('Gagal menyimpan jawaban: ' . json_encode($this->userJawabanModel->errors()), 500);
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
