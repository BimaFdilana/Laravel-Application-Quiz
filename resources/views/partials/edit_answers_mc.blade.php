<label>Jawaban Pilihan Ganda</label>
<div id="answers-section-{{ $qIndex }}" class="answers-section">
    @foreach ($question->answers as $aIndex => $answer)
        <div class="answer-item d-flex align-items-center gap-2 mb-2">
            <input type="hidden" name="questions[{{ $qIndex }}][answers][{{ $aIndex }}][id]" value="{{ $answer->id }}">
            <input type="text" class="form-control" name="questions[{{ $qIndex }}][answers][{{ $aIndex }}][answer_text]" value="{{ $answer->answer_text }}">
            <input type="file" class="form-control" name="questions[{{ $qIndex }}][answers][{{ $aIndex }}][image]">
            @if ($answer->image_url) <img src="{{ asset('storage/' . $answer->image_url) }}" class="img-thumbnail" width="50"> @endif
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="questions[{{ $qIndex }}][answers][{{ $aIndex }}][is_correct]" {{ $answer->is_correct ? 'checked' : '' }}>
                <label class="form-check-label">Benar</label>
            </div>
            <button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button>
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-secondary btn-sm add-answer" data-question="{{ $qIndex }}">Tambah Jawaban</button>
