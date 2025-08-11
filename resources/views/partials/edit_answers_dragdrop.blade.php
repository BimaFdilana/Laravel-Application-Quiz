<label>Potongan Jawaban</label>
<div id="drag-answers-section-{{ $qIndex }}" class="drag-answers-section">
    @foreach ($question->answers->sortBy('order') as $aIndex => $answer)
        <div class="drag-answer-item d-flex align-items-center gap-2 mb-2">
            <input type="hidden" name="questions[{{ $qIndex }}][drag_answers][{{ $aIndex }}][id]" value="{{ $answer->id }}">
            <span class="fw-bold">{{ $loop->iteration }}.</span>
            <input type="text" class="form-control" name="questions[{{ $qIndex }}][drag_answers][{{ $aIndex }}][text]" value="{{ $answer->answer_text }}">
            <input type="file" class="form-control" name="questions[{{ $qIndex }}][drag_answers][{{ $aIndex }}][image]">
            @if ($answer->image_url) <img src="{{ asset('storage/' . $answer->image_url) }}" class="img-thumbnail" width="50"> @endif
            <button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button>
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-secondary btn-sm add-drag-answer" data-question="{{ $qIndex }}">Tambah Potongan Jawaban</button>
