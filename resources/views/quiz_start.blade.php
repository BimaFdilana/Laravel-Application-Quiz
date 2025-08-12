<!DOCTYPE html>
<html data-bs-theme="light" lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kuis {{ $quiz->title }}</title>
    <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.5.5/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/quiz_start.css') }}">
</head>

<body class="bg-light">
    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <header class="text-center mb-4">
                    <h1 class="h3 fw-bold">📝 Kuis: {{ $quiz->title }}</h1>
                </header>

                <div class="card rounded-4 shadow-sm mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center p-3">
                        <div id="quiz-title" class="fw-bold">Soal 1 dari ...</div>
                        <div id="timer-container" class="fw-bold text-danger">
                            ⏳ Waktu: <span id="time-left">00:00</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 0 0 .5rem .5rem;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="progress-bar"
                            role="progressbar"></div>
                    </div>
                </div>

                <main id="quiz-container" class="p-lg-5 p-4 rounded-4 bg-white shadow-lg">
                    <div class="text-center mb-4">
                        <p id="question-text" class="mb-4"></p>
                        <div id="main-image-container" class="d-flex justify-content-center mb-3"
                            style="display: none;">
                        </div>
                    </div>
                    <div id="answers-container"></div>
                </main>

                <div id="result-container" class="d-none text-center p-4 rounded-4 bg-white shadow-lg mt-4">
                    <h2 class="mb-3 fw-bold text-success">Hebat, Kamu Berhasil!</h2>
                    <p class="lead" id="score-text"></p>
                    <a href="{{ route('post.detail', ['type' => $type, 'id' => $quizId]) }}"
                        class="btn btn-info rounded-pill text-white mt-3">Kembali ke Kelas</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.4.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="{{ asset('assets/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <script>
        let quizData = [];
        let userAnswers = [];
        let currentQuestionIndex = 0;
        let score = 0;
        let pointsPerQuestion = 0;
        let timeLeft = {{ $quiz->time_limit ?? 1200 }};
        let timerInterval;

        function startTimer() {
            timerInterval = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timeLeft = 0;
                    endQuiz();
                } else {
                    timeLeft--;
                    updateTimerDisplay();
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById("time-left").innerText =
                `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function updateProgressBar() {
            const progress = quizData.length > 0 ? Math.round(((currentQuestionIndex + 1) / quizData.length) * 100) : 0;
            const progressBar = document.getElementById("progress-bar");
            progressBar.style.width = `${progress}%`;
        }

        function endQuiz() {
            Swal.fire({
                title: "Waktu Habis!",
                text: "Waktu untuk mengerjakan kuis telah habis.",
                icon: "warning",
                confirmButtonText: "Lihat Hasil"
            }).then(() => {
                showResult();
            });
        }

        function loadQuizData(quizId) {
            fetch(`/quiz/${quizId}/questions`)
                .then(response => response.json())
                .then(data => {
                    quizData = data.questions.map(question => ({
                        id: question.id,
                        type: question.type,
                        question: question.question_text,
                        image: question.image_url,
                        answers: question.answers.map(answer => ({
                            id: answer.id,
                            text: answer.answer_text,
                            isCorrect: answer.is_correct,
                            image: answer.image_url,
                            order: answer.order
                        }))
                    }));
                    pointsPerQuestion = quizData.length > 0 ? Math.round(100 / quizData.length) : 0;
                    displayQuestion();
                    startTimer();
                })
                .catch(error => console.error("Error loading quiz data:", error));
        }

        function displayQuestion() {
            if (currentQuestionIndex >= quizData.length) return;
            const question = quizData[currentQuestionIndex];
            const answersContainer = document.getElementById("answers-container");
            const mainImageContainer = document.getElementById('main-image-container');

            answersContainer.innerHTML = "";
            mainImageContainer.innerHTML = "";
            mainImageContainer.style.display = 'none';

            document.getElementById("quiz-title").innerText = `Soal ${currentQuestionIndex + 1} dari ${quizData.length}`;
            document.getElementById("question-text").innerText = question.question || '';

            if (question.type === 'puzzle') {
                displayPuzzleQuestion(question);
            } else if (question.type === 'drag_drop') {
                if (question.image) {
                    mainImageContainer.innerHTML =
                        `<img id="question-image" class="rounded img-fluid shadow-sm" src="{{ asset('storage') }}/${question.image}" alt="Gambar Soal" style="max-height: 300px;">`;
                    mainImageContainer.style.display = 'flex';
                }
                displayDragDropQuestion(question);
            } else {
                if (question.image) {
                    mainImageContainer.innerHTML =
                        `<img id="question-image" class="rounded img-fluid shadow-sm" src="{{ asset('storage') }}/${question.image}" alt="Gambar Soal" style="max-height: 300px;">`;
                    mainImageContainer.style.display = 'flex';
                }
                displayMultipleChoiceQuestion(question);
            }
            updateProgressBar();
        }

        function displayMultipleChoiceQuestion(question) {
            let answersHtml = '<div class="row row-cols-1 row-cols-sm-2 g-3">';
            question.answers.forEach((answer, index) => {
                const label = String.fromCharCode(65 + index);
                answersHtml += `
                    <div class="col">
                        <div class="answer-card p-3 d-flex align-items-center gap-3" onclick="checkAnswer(${answer.isCorrect}, ${answer.id})">
                            <div class="answer-label">${label}</div>
                            <div class="answer-text-wrapper text-start">
                                ${answer.text ? `<p class="mb-1 fw-semibold">${answer.text}</p>` : ''}
                                ${answer.image ? `<img src="{{ asset('storage') }}/${answer.image}" class="img-fluid rounded" style="max-height:100px;">` : ''}
                            </div>
                        </div>
                    </div>`;
            });
            answersHtml += '</div>';
            document.getElementById("answers-container").innerHTML = answersHtml;
        }

        function displayDragDropQuestion(question) {
            const shuffledAnswers = [...question.answers].sort(() => Math.random() - 0.5);
            let targetsHtml =
                `
                <p class="text-center text-muted small">Susunlah urutan yang benar di bawah ini.</p>
                <div id="drag-drop-targets" class="row g-2 justify-content-center align-items-stretch">`; // Tambah align-items-stretch
            for (let i = 0; i < question.answers.length; i++) {
                targetsHtml +=
                    `<div class="col-md-3 col-6 d-flex">
                        <div class="drop-target d-flex align-items-center justify-content-center w-100">
                            <small class="text-muted">Kotak ${i + 1}</small>
                        </div>
                     </div>`;
            }
            targetsHtml += `</div>`;

            let sourceHtml =
                `<hr class="my-4"><p class="text-center fw-bold">Pilihan Jawaban</p><div id="drag-drop-source" class="drag-source">`;
            shuffledAnswers.forEach(answer => {
                sourceHtml += `<div class="answer-card p-2 text-center d-flex flex-column justify-content-center" data-id="${answer.id}" style="min-height: 120px;">
                                ${answer.image ? `<img src="{{ asset('storage') }}/${answer.image}" class="img-fluid rounded mb-2" style="max-height:60px;">` : ''}
                                ${answer.text ? `<p class="mb-0 small fw-semibold">${answer.text}</p>` : ''}
                               </div>`;
            });
            sourceHtml += `</div>`;

            document.getElementById("answers-container").innerHTML =
                `${targetsHtml}${sourceHtml}<div class="text-center mt-4"><button class="btn btn-primary" onclick="checkDragDropAnswer()">Periksa Jawaban</button></div>`;

            const sourceEl = document.getElementById('drag-drop-source');
            document.querySelectorAll('.drop-target').forEach(target => {
                new Sortable(target, {
                    group: 'shared-dnd',
                    animation: 150,
                    onAdd: function(evt) {
                        const placeholder = evt.to.querySelector('small');
                        if (placeholder) placeholder.style.display = 'none';
                    },
                    onRemove: function(evt) {
                        if (evt.to.children.length === 0) {
                            const placeholder = evt.to.querySelector('small');
                            if (placeholder) placeholder.style.display = 'block';
                        }
                    }
                });
            });
            new Sortable(sourceEl, {
                group: 'shared-dnd',
                animation: 150
            });
        }

        function displayPuzzleQuestion(question) {
            const answersContainer = document.getElementById("answers-container");

            let puzzleHtml = `
                <div class="text-center mb-4">
                    <p class="text-muted small">Lengkapi gambar berikut dengan menyeret potongan yang benar.</p>
                    <div class="puzzle-container">
                        <canvas id="puzzle-canvas" class="rounded shadow-sm" style="border: 2px solid #eee; max-width: 100%; height: auto;"></canvas>
                        <div id="puzzle-drop-zone"></div>
                    </div>
                </div>
                <div id="puzzle-choices-container">
                    <p class="text-center fw-bold">Pilih potongan yang cocok:</p>
                    <div id="puzzle-source" class="drag-source"></div>
                </div>
                <div class="text-center mt-4"><button class="btn btn-primary" onclick="checkPuzzleAnswer()">Periksa Jawaban</button></div>
            `;
            answersContainer.innerHTML = puzzleHtml;

            const mainImage = new Image();
            mainImage.crossOrigin = "Anonymous";
            mainImage.src = `{{ asset('storage') }}/${question.image}`;

            mainImage.onload = function() {
                const canvas = document.getElementById('puzzle-canvas');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                canvas.width = mainImage.width;
                canvas.height = mainImage.height;

                const gridCols = 3;
                const gridRows = 2;
                const pieceWidth = mainImage.width / gridCols;
                const pieceHeight = mainImage.height / gridRows;

                let allPieces = [];
                for (let r = 0; r < gridRows; r++) {
                    for (let c = 0; c < gridCols; c++) {
                        const pieceCanvas = document.createElement('canvas');
                        pieceCanvas.width = pieceWidth;
                        pieceCanvas.height = pieceHeight;
                        const pieceCtx = pieceCanvas.getContext('2d');

                        const cutX = c * pieceWidth;
                        const cutY = r * pieceHeight;

                        pieceCtx.drawImage(mainImage, cutX, cutY, pieceWidth, pieceHeight, 0, 0, pieceWidth,
                            pieceHeight);

                        allPieces.push({
                            id: `piece_${r}_${c}`,
                            image: pieceCanvas.toDataURL(),
                            row: r,
                            col: c
                        });
                    }
                }

                const correctPieceIndex = Math.floor(Math.random() * allPieces.length);
                const correctPiece = allPieces[correctPieceIndex];

                ctx.drawImage(mainImage, 0, 0);
                const holeX = correctPiece.col * pieceWidth;
                const holeY = correctPiece.row * pieceHeight;
                ctx.clearRect(holeX, holeY, pieceWidth, pieceHeight);

                const shuffledAnswers = allPieces.sort(() => Math.random() - 0.5);
                const sourceEl = document.getElementById('puzzle-source');
                if (!sourceEl) return;

                shuffledAnswers.forEach(piece => {
                    const isCorrect = (piece.row === correctPiece.row && piece.col === correctPiece.col);
                    sourceEl.innerHTML +=
                        `<div class="answer-card" data-id="${piece.id}" data-correct="${isCorrect}"><img src="${piece.image}" class="img-fluid"></div>`;
                });

                const dropZoneEl = document.getElementById('puzzle-drop-zone');
                if (!dropZoneEl) return;
                const scale = canvas.offsetWidth / mainImage.width;
                dropZoneEl.style.top = (holeY * scale) + 'px';
                dropZoneEl.style.left = (holeX * scale) + 'px';
                dropZoneEl.style.width = (pieceWidth * scale) + 'px';
                dropZoneEl.style.height = (pieceHeight * scale) + 'px';

                new Sortable(sourceEl, {
                    group: 'puzzle-group',
                    animation: 150
                });
                new Sortable(dropZoneEl, {
                    group: 'puzzle-group',
                    animation: 150,
                    onAdd: (evt) => {
                        if (dropZoneEl.children.length > 1) sourceEl.appendChild(evt.item);
                    }
                });
            };
        }

        function checkAnswer(isCorrect, chosenAnswerId = null) {
            userAnswers.push({
                question_id: quizData[currentQuestionIndex].id,
                answer_id: chosenAnswerId
            });
            if (isCorrect) {
                score += pointsPerQuestion;
                Swal.fire({
                    title: "Benar!",
                    icon: "success",
                    timer: 1200,
                    showConfirmButton: false
                }).then(nextQuestion);
            } else {
                Swal.fire({
                    title: "Salah!",
                    icon: "error",
                    timer: 1200,
                    showConfirmButton: false
                }).then(nextQuestion);
            }
        }

        function checkDragDropAnswer() {
            const currentQuestion = quizData[currentQuestionIndex];
            const dropTargets = document.querySelectorAll('#drag-drop-targets .drop-target');
            let isCorrect = true;
            let submittedAnswerIds = [];
            if (document.querySelectorAll('#drag-drop-targets .answer-card').length < currentQuestion.answers.length) {
                return Swal.fire('Oops!', 'Harap isi semua kotak jawaban.', 'warning');
            }
            dropTargets.forEach((target, index) => {
                const droppedItem = target.querySelector('.answer-card');
                if (!droppedItem) {
                    isCorrect = false;
                    return;
                }
                const droppedItemId = parseInt(droppedItem.dataset.id);
                submittedAnswerIds.push(droppedItemId);
                const originalAnswer = currentQuestion.answers.find(a => a.id === droppedItemId);
                if (!originalAnswer || originalAnswer.order !== (index + 1)) isCorrect = false;
            });
            userAnswers.push({
                question_id: currentQuestion.id,
                answer_id: submittedAnswerIds
            });
            checkAnswer(isCorrect);
        }

        function checkPuzzleAnswer() {
            const dropZone = document.getElementById('puzzle-drop-zone');
            const droppedItem = dropZone.querySelector('.answer-card');
            if (!droppedItem) return Swal.fire('Oops!', 'Silakan seret satu potongan gambar ke dalam kotak.', 'warning');

            checkAnswer(droppedItem.dataset.correct == 'true', droppedItem.dataset.id);
        }

        function nextQuestion() {
            currentQuestionIndex++;
            if (currentQuestionIndex < quizData.length) {
                displayQuestion();
            } else {
                clearInterval(timerInterval);
                updateProgressBar();
                showResult();
            }
        }

        function showResult() {
            Swal.fire({
                title: '🎉 Selamat!',
                text: `Kamu telah menyelesaikan kuis! Skor kamu: ${Math.round(score)} dari 100`,
                imageUrl: 'https://cdn-icons-png.flaticon.com/512/3159/3159066.png',
                imageWidth: 100,
                imageHeight: 100,
                confirmButtonText: 'Lihat Hasil 🎯',
            }).then(() => {
                document.getElementById("quiz-container").classList.add("d-none");
                document.getElementById("result-container").classList.remove("d-none");
                document.getElementById("score-text").innerText = `Skor Akhir Kamu: ${Math.round(score)}`;
                saveResult();
                confetti({
                    particleCount: 150,
                    spread: 90,
                    origin: {
                        y: 0.6
                    }
                });
            });
        }

        function saveResult() {
            fetch('/save-quiz-result', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    quiz_id: quizId,
                    student_id: userId,
                    score: Math.round(score),
                    answers: userAnswers,
                }),
            }).then(response => response.json()).catch(error => console.error("Error:", error));
        }

        const userId = {{ Auth::user()->id }};
        const quizId = {{ $quiz->id }};
        window.onload = () => loadQuizData(quizId);
    </script>
</body>

</html>
