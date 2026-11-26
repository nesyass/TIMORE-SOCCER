@php
    $profile = data_get($record ?? null, 'payload.content', []);
    $saved = $key === 'career' ? \App\Models\AdminContentFields::coachCareer($profile) : ($profile[$key] ?? []);
    $entries = $value('content.'.$key, $saved);
    $entries = is_array($entries) ? array_slice($entries, 0, 10, true) : [];
    $numericKeys = array_filter(array_keys($entries), 'is_int');
    $next = $numericKeys ? max($numericKeys) + 1 : 0;
    $singular = $key === 'career' ? 'Pengalaman' : 'Akun media sosial';
@endphp
<fieldset class="repeat-field wide" data-repeat data-next="{{ $next }}">
    <legend>{{ $label }}</legend>
    <input type="hidden" name="content[{{ $key }}]" value="">
    <div class="repeat-items" data-repeat-items>
        @foreach($entries as $index => $entry)
            @include('admin.content-repeat-item', ['entry' => is_array($entry) ? $entry : [], 'index' => $index, 'ordinal' => $loop->iteration])
        @endforeach
    </div>
    @if($recover) @error('content.'.$key)<small class="field-error">{{ $message }}</small>@enderror @endif
    <button class="btn mini repeat-add" type="button" data-add-repeat @disabled(count($entries) >= 10)><span aria-hidden="true">+</span> {{ $hint }}</button>
    <small class="repeat-feedback" data-repeat-feedback role="status"></small>
    <template data-repeat-template>
        @include('admin.content-repeat-item', ['entry' => [], 'index' => '__ROW__', 'ordinal' => '', 'recover' => false])
    </template>
</fieldset>
