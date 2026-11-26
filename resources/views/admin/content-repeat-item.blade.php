<fieldset class="repeat-item" data-repeat-item>
    <legend>{{ $singular }} <span data-repeat-ordinal>{{ $ordinal }}</span></legend>
    <div class="data-form">
        @foreach($argument as $subKey => [$subLabel, $subType, $limit, $required])
            @php
                $subId = $prefix.'-'.$key.'-'.$index.'-'.$subKey;
                $subError = 'content.'.$key.'.'.$index.'.'.$subKey;
                $invalid = $recover && $errors->has($subError);
                $current = $entry[$subKey] ?? '';
            @endphp
            <div class="field {{ in_array($subKey, ['url', 'detail'], true) ? 'wide' : '' }}">
                <label for="{{ $subId }}">{{ $subLabel }} @unless($required)<span>(opsional)</span>@endunless</label>
                @if($subType === 'select')
                    <select id="{{ $subId }}" name="content[{{ $key }}][{{ $index }}][{{ $subKey }}]" @required($required)
                        @if($invalid) aria-invalid="true" aria-describedby="{{ $subId }}-error" @endif>
                        <option value="">Pilih platform</option>
                        @foreach($limit as $platform => $platformLabel)<option value="{{ $platform }}" @selected($current === $platform)>{{ $platformLabel }}</option>@endforeach
                    </select>
                @else
                    <input id="{{ $subId }}" type="{{ $subType }}" name="content[{{ $key }}][{{ $index }}][{{ $subKey }}]" maxlength="{{ $limit }}" value="{{ $current }}" @required($required)
                        @if($subType === 'url') placeholder="https://" @endif @if($invalid) aria-invalid="true" aria-describedby="{{ $subId }}-error" @endif>
                @endif
                @if($invalid)<small id="{{ $subId }}-error" class="field-error">{{ $errors->first($subError) }}</small>@endif
            </div>
        @endforeach
    </div>
    <button type="button" class="btn mini ghost repeat-remove" data-remove-repeat>Hapus baris<span class="sr-only"> {{ $singular }} <span data-repeat-ordinal>{{ $ordinal }}</span></span></button>
</fieldset>