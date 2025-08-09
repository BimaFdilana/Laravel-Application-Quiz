<!DOCTYPE html>
<html data-bs-theme="light" lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kuis {{ $quiz->title }}</title>
    <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.5.5/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/start.css') }}">

    <style>
        .drop-target {
            border: 2px dashed #ccc;
            border-radius: .5rem;
            width: 100%;
            min-height: 150px;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
        }

        .drop-target.drag-over {
            background-color: #e9f5ff;
        }

        .drop-target-placeholder {
            color: #aaa;
        }
        .drag-source {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: .5rem;
            min-height: 140px;
        }

        .answer-card {
            cursor: grab;
            background-color: white;
            padding: 0.5rem;
            border-radius: .5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .answer-card img.answer-img {
            max-height: 80px;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="container mb-2">
                    <div class="row">
                        <div class="col text-start">
                            <p id="quiz-title" class="text-dark mb-2 fw-normal"></p>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-primary" id="progress-bar" role="progressbar" style="width: 0%;"
                            aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <div id="quiz-container" class="p-4 rounded-4 bg-white shadow-lg">
                    <div class="text-center mb-4">
                        <p id="question-text" class="fs-5 fw-normal"></p>
                        <div class="d-flex justify-content-center mb-3">
                            <img id="question-image" class="rounded img-fluid shadow-sm" src=""
                                alt="Question Image" style="max-height: 250px; display: none;">
                        </div>
                    </div>

                    <div id="answers-container"></div>

                    <div id="timer-container" class="text-center mt-4">
                        <p id="timer" class="fs-5 fw-bold text-danger">Waktu tersisa: <span
                                id="time-left">00:00</span></p>
                    </div>
                </div>

                <div id="result-container" class="d-none text-center p-4 rounded-4 bg-white shadow-lg mt-4">
                    <h4 class="mb-3 fw-bold text-success">Hasil Kuis</h4>
                    <p class="lead" id="score-text"></p>
                    <a href="{{ route('post.detail', ['type' => $type, 'id' => $quizId]) }}"
                        class="btn btn-restart mt-3">Kembali</a>
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
                if (timeLeft > 0) {
                    timeLeft--;
                    updateTimerDisplay();
                } else {
                    clearInterval(timerInterval);
                    endQuiz();
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
            const progress = Math.round(((currentQuestionIndex + 1) / quizData.length) * 100);
            const progressBar = document.getElementById("progress-bar");
            progressBar.style.width = `${progress}%`;
            progressBar.setAttribute("aria-valuenow", progress);
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

                    pointsPerQuestion = Math.round(100 / quizData.length);
                    displayQuestion();
                    startTimer();
                })
                .catch(error => console.error("Error loading quiz data:", error));
        }

        function displayQuestion() {
            const question = quizData[currentQuestionIndex];
            const answersContainer = document.getElementById("answers-container");
            answersContainer.innerHTML = "";

            document.getElementById("quiz-title").innerText = `Soal ${currentQuestionIndex + 1} dari ${quizData.length}`;
            document.getElementById("question-text").innerText = question.question;
            const questionImage = document.getElementById("question-image");
            if (question.image) {
                questionImage.src = `{{ asset('storage') }}/${question.image}`;
                questionImage.style.display = 'block';
            } else {
                questionImage.style.display = 'none';
            }

            if (question.type === 'drag_drop') {
                displayDragDropQuestion(question);
            } else {
                displayMultipleChoiceQuestion(question);
            }

            updateProgressBar();
        }

        function displayMultipleChoiceQuestion(question) {
            const answersContainer = document.getElementById("answers-container");
            const optionClasses = ['answer-option-a', 'answer-option-b', 'answer-option-c', 'answer-option-d'];

            let answersHtml = '<div class="row row-cols-1 row-cols-sm-2 g-3">';
            question.answers.forEach((answer, index) => {
                const label = String.fromCharCode(65 + index);
                answersHtml += `
                    <div class="col">
                        <div class="answer-card p-3 ${optionClasses[index]} text-dark" onclick="checkAnswer(${answer.isCorrect}, ${answer.id})" role="button">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <div class="answer-label">${label}.</div>
                                <div class="answer-text-wrapper">
                                    ${answer.text ? `<p class="mb-1">${answer.text}</p>` : ''}
                                    ${answer.image ? `<img src="{{ asset('storage') }}/${answer.image}" class="img-fluid rounded answer-img mt-2">` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            answersHtml += '</div>';
            answersContainer.innerHTML = answersHtml;
        }

        function displayDragDropQuestion(question) {
            const answersContainer = document.getElementById("answers-container");
            const shuffledAnswers = [...question.answers].sort(() => Math.random() - 0.5);

            let targetsHtml = `
                <div class="row">
                    <div class="col-12 mb-3">
                        <p class="text-center text-muted">Seret jawaban dari bawah ke dalam kotak yang sesuai.</p>
                        <div id="drag-drop-targets" class="row g-2 justify-content-center">
            `;
            for (let i = 0; i < question.answers.length; i++) {
                targetsHtml += `
                    <div class="col-md-3 col-6">
                        <div class="drop-target" data-order="${i + 1}">
                            <small class="drop-target-placeholder">Letakkan di sini</small>
                        </div>
                    </div>
                `;
            }
            targetsHtml += `</div></div></div>`;

            let sourceHtml = `
                <div class="row">
                    <hr class="my-4">
                    <div class="col-12">
                        <p class="text-center fw-bold">Pilihan Jawaban</p>
                        <div id="drag-drop-source" class="drag-source d-flex flex-wrap justify-content-center gap-2">
            `;
            shuffledAnswers.forEach(answer => {
                sourceHtml += `
                    <div class="answer-card" data-id="${answer.id}" style="flex-basis: calc(25% - 0.5rem); min-width: 120px;">
                        ${answer.image ? `<img src="{{ asset('storage') }}/${answer.image}" class="img-fluid rounded answer-img">` : ''}
                        ${answer.text ? `<p class="mb-0 small">${answer.text}</p>` : ''}
                    </div>
                `;
            });
            sourceHtml += `</div></div></div>`;

            answersContainer.innerHTML = `
                ${targetsHtml}
                ${sourceHtml}
                <div class="text-center mt-4">
                    <button id="check-drag-drop-btn" class="btn btn-primary" onclick="checkDragDropAnswer()">Periksa Jawaban</button>
                </div>
            `;

            const sourceEl = document.getElementById('drag-drop-source');
            new Sortable(sourceEl, {
                group: 'shared-dnd',
                animation: 150
            });

            document.querySelectorAll('.drop-target').forEach(target => {
                new Sortable(target, {
                    group: 'shared-dnd',
                    animation: 150,
                    onAdd: function(evt) {
                        const placeholder = evt.to.querySelector('.drop-target-placeholder');
                        if (placeholder) placeholder.style.display = 'none';

                        if (evt.to.children.length > 2) {
                            const itemToMoveBack = evt.item;
                            sourceEl.appendChild(itemToMoveBack);
                        }
                    },
                    onRemove: function(evt) {
                        const placeholder = evt.to.querySelector('.drop-target-placeholder');
                        if (evt.to.children.length === 1 && placeholder) {
                            placeholder.style.display = 'block';
                        }
                    }
                });
            });
        }

        function checkAnswer(isCorrect, chosenAnswerId = null) {
            const questionId = quizData[currentQuestionIndex].id;

            userAnswers.push({
                question_id: questionId,
                answer_id: chosenAnswerId
            });

            if (isCorrect) {
                score += pointsPerQuestion;
                Swal.fire({
                    title: "Benar!",
                    text: "Jawaban Anda benar!",
                    icon: "success",
                    timer: 1000,
                    showConfirmButton: false
                }).then(nextQuestion);
            } else {
                Swal.fire({
                    title: "Salah!",
                    text: "Cobalah lagi di soal berikutnya.",
                    icon: "error",
                    timer: 1000,
                    showConfirmButton: false
                }).then(nextQuestion);
            }
        }


        function checkDragDropAnswer() {
            const currentQuestion = quizData[currentQuestionIndex];

            const dropTargets = document.querySelectorAll('#drag-drop-targets .drop-target');
            let isCorrect = true;
            let submittedAnswerIds = [];

            const droppedItems = document.querySelectorAll('#drag-drop-targets .answer-card');
            if (droppedItems.length < currentQuestion.answers.length) {
                Swal.fire('Oops!', 'Harap isi semua kotak jawaban terlebih dahulu.', 'warning');
                return;
            }

            dropTargets.forEach((target, index) => {
                const droppedItem = target.querySelector('.answer-card[data-id]');
                const expectedOrder = index + 1;

                if (!droppedItem) {
                    isCorrect = false;
                    return;
                }

                const droppedItemId = parseInt(droppedItem.dataset.id);
                submittedAnswerIds.push(droppedItemId);

                const originalAnswer = currentQuestion.answers.find(a => a.id === droppedItemId);

                if (!originalAnswer || originalAnswer.order !== expectedOrder) {
                    isCorrect = false;
                }
            });


            userAnswers.push({
                question_id: currentQuestion.id,
                answer_id: submittedAnswerIds
            });

            checkAnswer(isCorrect);
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
                text: `Kamu telah menyelesaikan kuis! Skor kamu: ${score} dari 100`,
                imageUrl: 'https://cdn-icons-png.flaticon.com/512/3159/3159066.png',
                imageWidth: 100,
                imageHeight: 100,
                background: '#fff3cd',
                confirmButtonColor: '#ff6f61',
                confirmButtonText: 'Lihat Hasil 🎯',
                customClass: {
                    popup: 'rounded-4 shadow-lg'
                }
            }).then(() => {
                document.getElementById("quiz-container").classList.add("d-none");
                document.getElementById("result-container").classList.remove("d-none");
                document.getElementById("score-text").innerText = `Skor Kamu: ${score} dari 100`;
                saveResult();
                confetti({
                    particleCount: 1000,
                    angle: -90,
                    spread: 180,
                    startVelocity: 70,
                    gravity: 1,
                    origin: {
                        x: 0.5,
                        y: -1
                    },
                    zIndex: 9999
                });
            });
        }

        function saveResult() {
            fetch('/save-quiz-result', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        quiz_id: quizId,
                        student_id: userId,
                        score: score,
                        answers: userAnswers,
                    }),
                }).then(response => response.json())
                .then(data => console.log("Result saved:", data))
                .catch(error => console.error("Error:", error));
        }

        const userId = {{ Auth::user()->id }};
        const quizId = {{ $quiz->id }};
        window.onload = () => loadQuizData(quizId);
    </script>
</body>

</html>
