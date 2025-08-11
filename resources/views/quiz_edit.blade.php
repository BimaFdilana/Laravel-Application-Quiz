@extends('components.main')
@section('title', 'Edit Kuis')

@section('content')
    <div class="container mt-4">
        <div class="row gx-3 gy-3">
            <div class="col col-12">
                <div class="card h-100 rounded-3 overflow-hidden border-0 bg-light">
                    <div class="card-body bg-light d-flex flex-column gap-1 p-2">
                        <h4 class="fw-bold">Edit Kuis</h4>
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>Oops! Ada yang salah dengan input Anda:</strong>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form action="{{ route('quizzes.update', ['classroom_id' => $classroom->id, 'id' => $quiz->id]) }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="form-group mb-3">
                                <label for="title">Judul Kuis</label>
                                <input class="form-control" type="text" name="title" id="title"
                                    value="{{ old('title', $quiz->title) }}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="level">Tingkat Kesulitan</label>
                                <select name="level" id="level" class="form-control" required>
                                    <option value="Mudah" @if (old('level', $quiz->level) == 'Mudah') selected @endif>Mudah</option>
                                    <option value="Sedang" @if (old('level', $quiz->level) == 'Sedang') selected @endif>Sedang</option>
                                    <option value="Sulit" @if (old('level', $quiz->level) == 'Sulit') selected @endif>Sulit</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label for="time_limit">Durasi Kuis</label>
                                <select name="time_limit" id="time_limit" class="form-control" required>
                                    <option value="600" @if (old('time_limit', $quiz->time_limit) == 600) selected @endif>10 menit
                                    </option>
                                    <option value="1200" @if (old('time_limit', $quiz->time_limit) == 1200) selected @endif>20 menit
                                    </option>
                                    <option value="1500" @if (old('time_limit', $quiz->time_limit) == 1500) selected @endif>25 menit
                                    </option>
                                    <option value="3000" @if (old('time_limit', $quiz->time_limit) == 3000) selected @endif>50 menit
                                    </option>
                                    <option value="6000" @if (old('time_limit', $quiz->time_limit) == 6000) selected @endif>100 menit
                                    </option>
                                    <option value="7200" @if (old('time_limit', $quiz->time_limit) == 7200) selected @endif>120 menit
                                    </option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label for="description">Deskripsi</label>
                                <textarea class="form-control" name="description" id="description" rows="4" required>{{ old('description', $quiz->description) }}</textarea>
                            </div>

                            <div id="questions-section">
                                <h5 class="fw-bold mt-3">Soal</h5>
                                @foreach ($quiz->questions as $qIndex => $question)
                                    <div class="question-item border p-3 mb-3 rounded">
                                        <button type="button" class="btn-close float-end remove-question"
                                            aria-label="Close"></button>
                                        <input type="hidden" name="questions[{{ $qIndex }}][id]"
                                            value="{{ $question->id }}">

                                        <div class="form-group mb-2">
                                            <label>Jenis Soal</label>
                                            <select class="form-control question-type-select"
                                                name="questions[{{ $qIndex }}][type]">
                                                <option value="multiple_choice"
                                                    @if ($question->type == 'multiple_choice') selected @endif>Pilihan Ganda
                                                </option>
                                                <option value="drag_drop"
                                                    @if ($question->type == 'drag_drop') selected @endif>Drag & Drop
                                                    (Menyusun)</option>
                                                <option value="puzzle" @if ($question->type == 'puzzle') selected @endif>
                                                    Puzzle Potongan Hilang</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label>Teks Soal</label>
                                            <textarea class="form-control" name="questions[{{ $qIndex }}][question_text]" rows="2">{{ $question->question_text }}</textarea>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="question-image-label">Gambar Soal</label>
                                            <input type="file" class="form-control question-image-input"
                                                name="questions[{{ $qIndex }}][image]">
                                            @if ($question->image_url)
                                                <img src="{{ asset('storage/' . $question->image_url) }}"
                                                    class="img-thumbnail mt-2" width="100">
                                            @endif
                                            <small class="form-text text-danger">Rekomendasi: 1280x720px, di bawah 200
                                                KB.</small>
                                        </div>

                                        <div class="multiple-choice-options"
                                            style="{{ $question->type !== 'multiple_choice' ? 'display: none;' : '' }}">
                                            @include('partials.edit_answers_mc', [
                                                'question' => $question,
                                                'qIndex' => $qIndex,
                                            ])
                                        </div>

                                        <div class="drag-drop-options"
                                            style="{{ $question->type !== 'drag_drop' ? 'display: none;' : '' }}">
                                            @include('partials.edit_answers_dragdrop', [
                                                'question' => $question,
                                                'qIndex' => $qIndex,
                                            ])
                                        </div>

                                        <div class="puzzle-options"
                                            style="{{ $question->type !== 'puzzle' ? 'display: none;' : '' }}">
                                            @include('partials.edit_answers_puzzle', [
                                                'question' => $question,
                                                'qIndex' => $qIndex,
                                            ])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="d-block">
                                <button type="button" class="btn btn-secondary btn-sm add-question">Tambah Soal</button>
                            </div>
                            <div class="d-block text-end">
                                <button class="btn btn-primary mt-3" type="submit">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="question-template">
        <div class="question-item border p-3 mb-3 rounded">
            <button type="button" class="btn-close float-end remove-question" aria-label="Close"></button>
            <div class="form-group mb-2">
                <label>Jenis Soal</label>
                <select class="form-control question-type-select" name="questions[{index}][type]">
                    <option value="multiple_choice" selected>Pilihan Ganda</option>
                    <option value="drag_drop">Drag & Drop (Menyusun)</option>
                    <option value="puzzle">Puzzle Potongan Hilang</option>
                </select>
            </div>
            <div class="form-group mb-2">
                <label>Teks Soal</label>
                <textarea class="form-control" name="questions[{index}][question_text]" rows="2"></textarea>
            </div>
            <div class="form-group mb-2">
                <label class="question-image-label">Gambar Soal</label>
                <input type="file" class="form-control question-image-input" name="questions[{index}][image]">
                <small class="form-text text-danger">Rekomendasi: maks. 1280x720px, di bawah 200 KB.</small>
            </div>

            <div class="multiple-choice-options">
                <label>Jawaban Pilihan Ganda</label>
                <div id="answers-section-{index}" class="answers-section"></div>
                <button type="button" class="btn btn-secondary btn-sm add-answer" data-question="{index}">Tambah
                    Jawaban</button>
            </div>

            <div class="drag-drop-options" style="display: none;">
                <label>Potongan Jawaban</label>
                <div id="drag-answers-section-{index}" class="drag-answers-section"></div>
                <button type="button" class="btn btn-secondary btn-sm add-drag-answer" data-question="{index}">Tambah
                    Potongan Jawaban</button>
            </div>

            <div class="puzzle-options" style="display: none;">
                <label>Gambar Pengecoh (Potongan yang salah)</label>
                <div id="puzzle-answers-section-{index}" class="puzzle-answers-section"></div>
                <button type="button" class="btn btn-secondary btn-sm add-puzzle-answer" data-question="{index}">Tambah
                    Gambar Pengecoh</button>
            </div>
        </div>
    </template>

    <script>
        // SCRIPT INI SAMA PERSIS DENGAN DI HALAMAN 'BUAT KUIS'
        let questionIndex = {{ $quiz->questions->count() }};

        document.querySelector('.add-question').addEventListener('click', function() {
            const template = document.getElementById('question-template').innerHTML;
            const section = document.getElementById('questions-section');
            const newQuestionHtml = template.replace(/{index}/g, questionIndex);
            section.insertAdjacentHTML('beforeend', newQuestionHtml);
            questionIndex++;
        });

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('add-answer')) {
                const qIndex = event.target.dataset.question;
                const section = document.getElementById(`answers-section-${qIndex}`);
                const aIndex = section.children.length;
                const item =
                `<div class="answer-item d-flex align-items-center gap-2 mb-2">...</div>`;
                section.insertAdjacentHTML('beforeend', item);
            }

            if (event.target.classList.contains('add-drag-answer')) {
                const qIndex = event.target.dataset.question;
                const section = document.getElementById(`drag-answers-section-${qIndex}`);
                const aIndex = section.children.length;
                const item =
                `<div class="drag-answer-item d-flex align-items-center gap-2 mb-2">...</div>`;
                section.insertAdjacentHTML('beforeend', item);
            }

            if (event.target.classList.contains('add-puzzle-answer')) {
                const qIndex = event.target.dataset.question;
                const section = document.getElementById(`puzzle-answers-section-${qIndex}`);
                const aIndex = section.children.length;
                const item = `
                    <div class="puzzle-answer-item d-flex align-items-center gap-2 mb-2">
                        <input type="file" class="form-control" name="questions[${qIndex}][puzzle_answers][${aIndex}][image]" required>
                        <label class="form-check-label small text-muted">Gambar Pengecoh</label>
                        <button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button>
                    </div>`;
                section.insertAdjacentHTML('beforeend', item);
            }

            if (event.target.classList.contains('remove-item')) {
                event.target.closest('.d-flex').remove();
            }
            if (event.target.classList.contains('remove-question')) {
                event.target.closest('.question-item').remove();
            }
        });

        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('question-type-select')) {
                const selectedType = event.target.value;
                const questionItem = event.target.closest('.question-item');
                const imageLabel = questionItem.querySelector('.question-image-label');

                questionItem.querySelector('.multiple-choice-options').style.display = 'none';
                questionItem.querySelector('.drag-drop-options').style.display = 'none';
                questionItem.querySelector('.puzzle-options').style.display = 'none';

                if (selectedType === 'puzzle') {
                    questionItem.querySelector('.puzzle-options').style.display = 'block';
                    imageLabel.textContent = 'Gambar Puzzle Lengkap (Sistem akan memotongnya otomatis)';
                } else if (selectedType === 'drag_drop') {
                    questionItem.querySelector('.drag-drop-options').style.display = 'block';
                    imageLabel.textContent = 'Gambar Soal (Opsional)';
                } else {
                    questionItem.querySelector('.multiple-choice-options').style.display = 'block';
                    imageLabel.textContent = 'Gambar Soal (Opsional)';
                }
            }
        });
    </script>
@endsection
