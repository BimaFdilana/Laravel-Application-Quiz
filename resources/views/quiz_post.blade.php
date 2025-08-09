@extends('components.main')
@section('title', 'Buat Kuis')
@section('content')
    <div class="container mt-4">
        <div class="row gx-3 gy-3">
            <div class="col col-12">
                <div class="card h-100 rounded-3 overflow-hidden border-0 bg-light">
                    <div class="card-body bg-light d-flex flex-column gap-1 p-2">
                        <h4 class="fw-bold">Buat Kuis</h4>
                        <form action="{{ route('quizzes.store', ['classroom_id' => $classroom->id]) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                            <div class="form-group mb-3">
                                <label for="title">Judul Kuis</label>
                                <input class="form-control" type="text" name="title" id="title" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="level">Tingkat Kesulitan</label>
                                <select name="level" id="level" class="form-control" required>
                                    <option value="" disabled selected>Pilih Tingkat Kesulitan</option>
                                    <option value="Mudah">Mudah</option>
                                    <option value="Sedang">Sedang</option>
                                    <option value="Sulit">Sulit</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label for="time_limit">Durasi Kuis (1 Menit per Soal)</label>
                                <select name="time_limit" id="time_limit" class="form-control" required>
                                    <option value="" disabled selected>Pilih Durasi</option>
                                    <option value="600">10 menit</option>
                                    <option value="1200">20 menit</option>
                                    <option value="1500">25 menit</option>
                                    <option value="3000">50 menit</option>
                                    <option value="6000">100 menit</option>
                                    <option value="7200">120 menit</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label for="description">Deskripsi</label>
                                <textarea class="form-control" name="description" id="description" rows="4" required></textarea>
                            </div>

                            <div id="questions-section">
                                <h5 class="fw-bold mt-3">Soal</h5>
                            </div>
                            <div class="d-block">
                                <button type="button" class="btn btn-secondary btn-sm add-question">Tambah Soal</button>
                            </div>
                            <div class="d-block text-end">
                                <button class="btn btn-primary mt-3" type="submit">Posting</button>
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
                </select>
            </div>
            <div class="form-group mb-2">
                <label for="question_text_{index}">Teks Soal</label>
                <textarea class="form-control" name="questions[{index}][question_text]" rows="2"></textarea>
            </div>
            <div class="form-group mb-2">
                <label for="question_image_{index}">Gambar Soal</label>
                <input type="file" class="form-control" name="questions[{index}][image]">
            </div>

            <div class="multiple-choice-options">
                <label>Jawaban Pilihan Ganda</label>
                <div id="answers-section-{index}" class="answers-section"></div>
                <button type="button" class="btn btn-secondary btn-sm add-answer" data-question="{index}">Tambah
                    Jawaban</button>
            </div>

            <div class="drag-drop-options" style="display: none;">
                <label>Potongan Jawaban (Masukkan Sesuai Urutan yang Benar)</label>
                <div id="drag-answers-section-{index}" class="drag-answers-section"></div>
                <button type="button" class="btn btn-secondary btn-sm add-drag-answer" data-question="{index}">Tambah
                    Potongan Jawaban</button>
            </div>
        </div>
    </template>

    <script>
        let questionIndex = 0;

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
                const answersSection = document.getElementById(`answers-section-${qIndex}`);
                const answerIndex = answersSection.children.length;

                const answerItem = `
                    <div class="answer-item d-flex align-items-center gap-2 mb-2">
                        <input type="text" class="form-control" name="questions[${qIndex}][answers][${answerIndex}][answer_text]" placeholder="Teks Jawaban">
                        <input type="file" class="form-control" name="questions[${qIndex}][answers][${answerIndex}][image]">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="questions[${qIndex}][answers][${answerIndex}][is_correct]">
                            <label class="form-check-label">Benar</label>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button>
                    </div>`;
                answersSection.insertAdjacentHTML('beforeend', answerItem);
            }

            if (event.target.classList.contains('add-drag-answer')) {
                const qIndex = event.target.dataset.question;
                const dragAnswersSection = document.getElementById(`drag-answers-section-${qIndex}`);
                const dragAnswerIndex = dragAnswersSection.children.length;

                const dragAnswerItem = `
                    <div class="drag-answer-item d-flex align-items-center gap-2 mb-2">
                        <span class="fw-bold">${dragAnswerIndex + 1}.</span>
                        <input type="text" class="form-control" name="questions[${qIndex}][drag_answers][${dragAnswerIndex}][text]" placeholder="Teks Potongan Jawaban">
                        <input type="file" class="form-control" name="questions[${qIndex}][drag_answers][${dragAnswerIndex}][image]">
                        <button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button>
                    </div>`;
                dragAnswersSection.insertAdjacentHTML('beforeend', dragAnswerItem);
            }

            if (event.target.classList.contains('remove-item')) {
                event.target.parentElement.remove();
            }
            if (event.target.classList.contains('remove-question')) {
                event.target.closest('.question-item').remove();
            }
        });

        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('question-type-select')) {
                const selectedType = event.target.value;
                const questionItem = event.target.closest('.question-item');
                const multipleChoiceDiv = questionItem.querySelector('.multiple-choice-options');
                const dragDropDiv = questionItem.querySelector('.drag-drop-options');

                if (selectedType === 'drag_drop') {
                    multipleChoiceDiv.style.display = 'none';
                    dragDropDiv.style.display = 'block';
                } else {
                    multipleChoiceDiv.style.display = 'block';
                    dragDropDiv.style.display = 'none';
                }
            }
        });
    </script>
@endsection
