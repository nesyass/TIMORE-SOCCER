<fieldset class="wide content-fields">
    <legend>{{ match ($section) { 'coaches' => 'Profil pelatih', 'achievements' => 'Detail prestasi', default => 'Detail kegiatan' } }}</legend>
    <div class="data-form">
        @foreach($contentFields as $key => $field)
            @php
                [$label, $type] = $field;
                $argument = $field[2] ?? null;
                $hint = $field[3] ?? null;
                $id = $prefix.'-content-'.$key;
                $errorKey = 'content.'.$key;
                $invalid = $recover && $errors->has($errorKey);
                $description = trim(($hint ? $id.'-hint' : '').' '.($invalid ? $id.'-error' : ''));
                $current = $value($errorKey, data_get($record ?? null, 'payload.'.$errorKey));
            @endphp
            @if($type === 'repeat')
                @include('admin.content-repeat')
                @continue
            @endif
            <div class="field {{ $type === 'textarea' ? 'wide' : '' }}">
                <label for="{{ $id }}">{{ $label }}</label>
                @if($type === 'select')
                    <select id="{{ $id }}" name="content[{{ $key }}]" @if($invalid) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif>
                        <option value="">Belum ditentukan</option>
                        @foreach($argument as $option => $name)<option value="{{ $option }}" @selected($current === $option)>{{ $name }}</option>@endforeach
                    </select>
                @elseif($type === 'textarea')
                    <textarea id="{{ $id }}" name="content[{{ $key }}]" rows="{{ $argument > 500 ? 4 : 2 }}" maxlength="{{ $argument }}" @if($invalid) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif>{{ $current }}</textarea>
                @elseif($type === 'money')
                    <input id="{{ $id }}" type="number" inputmode="decimal" min="1" max="999999999999.99" step="0.01" name="content[{{ $key }}]" value="{{ $current }}" @if($invalid) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif>
                @else
                    <input id="{{ $id }}" type="{{ $type }}" name="content[{{ $key }}]" value="{{ $current }}" @if($argument) maxlength="{{ $argument }}" @endif @if($invalid) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif>
                @endif
                @if($hint)<small id="{{ $id }}-hint" class="meta-count">{{ $hint }}</small>@endif
                @if($invalid)<small id="{{ $id }}-error" class="field-error">{{ $errors->first($errorKey) }}</small>@endif
            </div>
        @endforeach
    </div>
</fieldset>
