@extends('components.main')
@section('title', 'Buat Kuis')

@section('content')
    <div class="container mt-4">
        <div class="row gx-3 gy-3">
            <div class="col col-12">
                <div class="card h-100 rounded-3 overflow-hidden border-0 bg-light">
                    <div class="card-body bg-light d-flex flex-column gap-1 p-2">
                        <h4 class="fw-bold">Buat Kuis</h4>
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
                                <label for="time_limit">Durasi Kuis</label>
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
                <small class="form-text text-danger">Rekomendasi: 1280x720px, di bawah 200 KB.</small>
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
                <div class="alert alert-info mt-3">
                    <h6 class="alert-heading">Soal Puzzle Otomatis</h6>
                    <p class="mb-0 small">Sistem akan otomatis memecah gambar utama menjadi 6 bagian dan memilih satu secara
                        acak sebagai jawaban.</p>
                </div>
            </div>
        </div>
    </template>

    <script>
        let questionIndex = 0;

        function initPuzzleEditor(questionItem) {
            const imageInput = questionItem.querySelector('.question-image-input');
            const previewImage = questionItem.querySelector('.puzzle-preview-image');
            const draggableZone = questionItem.querySelector('.draggable-zone');
            const posYInput = questionItem.querySelector('.pos-y-input');
            const posXInput = questionItem.querySelector('.pos-x-input');
            const widthInput = questionItem.querySelector('.width-input');
            const heightInput = questionItem.querySelector('.height-input');

            let scale = 1;
            previewImage.onload = () => {
                if (previewImage.offsetWidth > 0) {
                    scale = previewImage.naturalWidth / previewImage.offsetWidth;
                }
            };

            imageInput.addEventListener('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewImage.src = e.target.result;
                    };
                    reader.readAsDataURL(event.target.files[0]);
                }
            });

            interact(draggableZone)
                .draggable({
                    listeners: {
                        move(event) {
                            const target = event.target;
                            const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                            const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                            target.style.transform = `translate(${x}px, ${y}px)`;
                            target.setAttribute('data-x', x);
                            target.setAttribute('data-y', y);

                            posXInput.value = Math.round((target.offsetLeft + x) * scale);
                            posYInput.value = Math.round((target.offsetTop + y) * scale);
                        }
                    },
                    modifiers: [interact.modifiers.restrictRect({
                        restriction: 'parent'
                    })]
                })
                .resizable({
                    edges: {
                        left: true,
                        right: true,
                        bottom: true,
                        top: true
                    },
                    listeners: {
                        move(event) {
                            const target = event.target;
                            let x = parseFloat(target.getAttribute('data-x')) || 0;
                            let y = parseFloat(target.getAttribute('data-y')) || 0;

                            target.style.width = `${event.rect.width}px`;
                            target.style.height = `${event.rect.height}px`;

                            x += event.deltaRect.left;
                            y += event.deltaRect.top;

                            target.style.transform = `translate(${x}px, ${y}px)`;
                            target.setAttribute('data-x', x);
                            target.setAttribute('data-y', y);

                            posXInput.value = Math.round((target.offsetLeft + x) * scale);
                            posYInput.value = Math.round((target.offsetTop + y) * scale);
                            widthInput.value = Math.round(event.rect.width * scale);
                            heightInput.value = Math.round(event.rect.height * scale);
                        }
                    },
                    modifiers: [interact.modifiers.restrictSize({
                        min: {
                            width: 30,
                            height: 30
                        }
                    })]
                });
        }

        document.querySelector('.add-question').addEventListener('click', function() {
            const template = document.getElementById('question-template').innerHTML;
            const section = document.getElementById('questions-section');
            const newQuestionHtml = template.replace(/{index}/g, questionIndex);

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = newQuestionHtml;
            const newQuestionItem = tempDiv.firstElementChild;
            section.appendChild(newQuestionItem);

            initPuzzleEditor(newQuestionItem);
            questionIndex++;
        });

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('add-answer')) {
                const qIndex = event.target.dataset.question;
                const section = document.getElementById(`answers-section-${qIndex}`);
                const aIndex = section.children.length;
                const item =
                    `<div class="answer-item d-flex align-items-center gap-2 mb-2">...</div>`;
                section.insertAdjacentHTML('beforeend', item.replace(/{aIndex}/g, aIndex));
            }

            if (event.target.classList.contains('add-drag-answer')) {
                const qIndex = event.target.dataset.question;
                const section = document.getElementById(`drag-answers-section-${qIndex}`);
                const aIndex = section.children.length;
                const item =
                    `<div class="drag-answer-item d-flex align-items-center gap-2 mb-2">...</div>`;
                section.insertAdjacentHTML('beforeend', item.replace(/{aIndex}/g, aIndex));
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
