<label>Gambar Pengecoh</label>
<div id="puzzle-answers-section-{{ $qIndex }}" class="puzzle-answers-section">
    @foreach ($question->answers as $aIndex => $answer)
        <div class="puzzle-answer-item d-flex align-items-center gap-2 mb-2">
            <input type="hidden" name="questions[{{ $qIndex }}][puzzle_answers][{{ $aIndex }}][id]" value="{{ $answer->id }}">
            <input type="file" class="form-control" name="questions[{{ $qIndex }}][puzzle_answers][{{ $aIndex }}][image]">
            @if ($answer->image_url) <img src="{{ asset('storage/' . $answer->image_url) }}" class="img-thumbnail" width="50"> @endif
            <label class="form-check-label small text-muted">Gambar Pengecoh</label>
            <button type="button" class="btn btn-danger btn-sm remove-item">Hapus</button>
        </div>
    @endforeach
</div>
<button type="button" class="btn btn-secondary btn-sm add-puzzle-answer" data-question="{{ $qIndex }}">Tambah Gambar Pengecoh</button>
