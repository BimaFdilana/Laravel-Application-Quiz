<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Quiz;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuizResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function create($classroom_id)
    {
        $classroom = Classroom::findOrFail($classroom_id);
        return view('quiz_post', compact('classroom'));
    }

    public function store(Request $request, $classroom_id)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'time_limit' => 'required|integer',
            'level' => 'required|string',
            'questions' => 'required|array',
            'questions.*.type' => 'required|in:multiple_choice,drag_drop,puzzle',
            'questions.*.question_text' => 'required|string',
            'questions.*.image' => 'nullable|file|image',

            'questions.*.answers' => 'nullable|array',
            'questions.*.answers.*.answer_text' => 'nullable|string',
            'questions.*.answers.*.image' => 'nullable|file|image',
            'questions.*.answers.*.is_correct' => 'nullable|in:on',

            'questions.*.drag_answers' => 'nullable|array',
            'questions.*.drag_answers.*.text' => 'nullable|string',
            'questions.*.drag_answers.*.image' => 'nullable|file|image',

            'questions.*.puzzle_answers' => 'nullable|array',
            'questions.*.puzzle_answers.*.image' => 'required_if:questions.*.type,puzzle|file|image',
        ]);

        $quiz = Quiz::create([
            'classroom_id' => $validated['classroom_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'time_limit' => $validated['time_limit'],
            'level' => $validated['level'],
        ]);

        foreach ($validated['questions'] as $questionData) {
            $question = $quiz->questions()->create([
                'type' => $questionData['type'],
                'question_text' => $questionData['question_text'],
                'image_url' => isset($questionData['image']) ? $questionData['image']->store('questions', 'public') : null,
            ]);

            if ($questionData['type'] === 'multiple_choice' && !empty($questionData['answers'])) {
                foreach ($questionData['answers'] as $answerData) {
                    $question->answers()->create([
                        'answer_text' => $answerData['answer_text'],
                        'is_correct' => isset($answerData['is_correct']) && $answerData['is_correct'] === 'on' ? 1 : 0,
                        'image_url' => isset($answerData['image']) ? $answerData['image']->store('answers', 'public') : null,
                        'order' => 0,
                    ]);
                }
            } elseif ($questionData['type'] === 'drag_drop' && !empty($questionData['drag_answers'])) {
                foreach ($questionData['drag_answers'] as $order => $answerData) {
                    $question->answers()->create([
                        'answer_text' => $answerData['text'],
                        'is_correct' => 0,
                        'image_url' => isset($answerData['image']) ? $answerData['image']->store('answers', 'public') : null,
                        'order' => $order + 1,
                    ]);
                }
            } elseif ($questionData['type'] === 'puzzle' && !empty($questionData['puzzle_answers'])) {
                $correctIndex = $questionData['puzzle_correct_index'] ?? -1;
                foreach ($questionData['puzzle_answers'] as $aIndex => $answerData) {
                    $question->answers()->create([
                        'answer_text' => null,
                        'is_correct' => $aIndex == $correctIndex,
                        'image_url' => isset($answerData['image']) ? $answerData['image']->store('answers', 'public') : null,
                        'order' => 0,
                    ]);
                }
            }
        }

        return redirect()->route('classroom.show', $classroom_id)->with('success', 'Kuis berhasil dibuat!');
    }

    public function start(Quiz $quiz)
    {
        $quiz->load('questions.answers');

        $type = 'quiz';

        return view('quiz_start', [
            'quiz' => $quiz,
            'type' => $type,
            'quizId' => $quiz->id,
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $answers = $request->input('answers');

        $score = 0;
        foreach ($quiz->questions as $question) {
            $correctAnswer = $question->answers->firstWhere('is_correct', true);
            if (isset($answers[$question->id]) && $answers[$question->id] == $correctAnswer->id) {
                $score++;
            }
        }

        QuizResult::create([
            'student_id' => Auth::user()->id,
            'quiz_id' => $quiz->id,
            'score' => $score,
        ]);

        return redirect()
            ->route('quiz.start', $quiz->id)
            ->with('message', "Skor Anda: $score/" . $quiz->questions->count());
    }

    public function getQuizQuestions(Quiz $quiz)
    {
        $quiz->load('questions.answers');
        return response()->json($quiz);
    }

    public function saveResult(Request $request)
    {
        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'student_id' => 'required|exists:users,id',
            'score' => 'required|integer',
            'answers' => 'required|array',
        ]);

        QuizResult::create([
            'quiz_id' => $validated['quiz_id'],
            'student_id' => $validated['student_id'],
            'score' => $validated['score'],
            'answers' => json_encode($validated['answers']),
        ]);

        return response()->json(['message' => 'Result saved successfully!']);
    }

    public function edit($classroom_id, $quiz_id)
    {
        $classroom = Classroom::findOrFail($classroom_id);
        $quiz = Quiz::with('questions.answers')->findOrFail($quiz_id);

        return view('quiz_edit', compact('classroom', 'quiz'));
    }

    public function update(Request $request, $classroom_id, $quiz_id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'time_limit' => 'required|integer',
            'level' => 'required',
            'questions' => 'required|array',
            'questions.*.id' => 'nullable|exists:questions,id',
            'questions.*.type' => 'required|in:multiple_choice,drag_drop,puzzle',
            'questions.*.question_text' => 'required|string',
            'questions.*.image' => 'nullable|file|image',

            'questions.*.answers.*.id' => 'nullable|exists:answers,id',
            'questions.*.answers.*.answer_text' => 'nullable|string',
            'questions.*.answers.*.is_correct' => 'nullable|in:on',
            'questions.*.answers.*.image' => 'nullable|file|image',

            'questions.*.drag_answers' => 'nullable|array',
            'questions.*.drag_answers.*.id' => 'nullable|exists:answers,id',
            'questions.*.drag_answers.*.text' => 'nullable|string',
            'questions.*.drag_answers.*.image' => 'nullable|file|image',

            'questions.*.puzzle_answers' => 'nullable|array',
            'questions.*.puzzle_answers.*.id' => 'nullable|exists:answers,id',
            'questions.*.puzzle_answers.*.image' => 'nullable|file|image',
        ]);

        $quiz = Quiz::findOrFail($quiz_id);

        $quiz->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'time_limit' => $validated['time_limit'],
            'level' => $validated['level'],
        ]);

        $questionIds = collect($validated['questions'])->pluck('id')->filter()->toArray();
        $quiz->questions()->whereNotIn('id', $questionIds)->delete();

        foreach ($validated['questions'] as $questionData) {
            $question = Question::updateOrCreate(
                ['id' => $questionData['id'] ?? null, 'quiz_id' => $quiz->id],
                [
                    'type' => $questionData['type'],
                    'question_text' => $questionData['question_text'],
                    'image_url' => isset($questionData['image']) ? $questionData['image']->store('questions', 'public') : Question::find($questionData['id'] ?? 0)->image_url ?? null,
                ],
            );

            if ($questionData['type'] === 'multiple_choice' && !empty($questionData['answers'])) {
                $answerIds = collect($questionData['answers'])->pluck('id')->filter()->toArray();
                $question->answers()->whereNotIn('id', $answerIds)->delete();

                foreach ($questionData['answers'] as $answerData) {
                    Answer::updateOrCreate(
                        ['id' => $answerData['id'] ?? null, 'question_id' => $question->id],
                        [
                            'answer_text' => $answerData['answer_text'],
                            'is_correct' => isset($answerData['is_correct']) && $answerData['is_correct'] === 'on' ? 1 : 0,
                            'image_url' => isset($answerData['image']) ? $answerData['image']->store('answers', 'public') : Answer::find($answerData['id'] ?? 0)->image_url ?? null,
                            'order' => 0,
                        ],
                    );
                }
            } elseif ($questionData['type'] === 'drag_drop' && !empty($questionData['drag_answers'])) {
                $answerIds = collect($questionData['drag_answers'])->pluck('id')->filter()->toArray();
                $question->answers()->whereNotIn('id', $answerIds)->delete();

                foreach ($questionData['drag_answers'] as $order => $answerData) {
                    Answer::updateOrCreate(
                        ['id' => $answerData['id'] ?? null, 'question_id' => $question->id],
                        [
                            'answer_text' => $answerData['text'],
                            'is_correct' => 0,
                            'image_url' => isset($answerData['image']) ? $answerData['image']->store('answers', 'public') : Answer::find($answerData['id'] ?? 0)->image_url ?? null,
                            'order' => $order + 1,
                        ],
                    );
                }
            } elseif ($questionData['type'] === 'puzzle' && !empty($questionData['puzzle_answers'])) {
                foreach ($questionData['puzzle_answers'] as $answerData) {
                    if (!is_array($answerData)) {
                        continue;
                    }
                    $question->answers()->create([
                        'answer_text' => null,
                        'is_correct' => 0,
                        'image_url' => isset($answerData['image']) ? $answerData['image']->store('answers', 'public') : null,
                        'order' => 0,
                    ]);
                }
            }
        }

        return redirect()->route('classroom.show', $classroom_id)->with('success', 'Kuis berhasil diperbarui!');
    }

    public function destroy($classroom_id, $quiz_id)
    {
        $quiz = Quiz::findOrFail($quiz_id);

        $quiz->results()->delete();

        foreach ($quiz->questions as $question) {
            $question->answers()->delete();
        }
        $quiz->questions()->delete();

        $quiz->delete();

        return redirect()->route('classroom.show', $classroom_id)->with('success', 'Kuis berhasil dihapus!');
    }
}