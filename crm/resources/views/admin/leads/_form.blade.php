@csrf

<div class="form-grid">
    <div>
        <label for="business_name">Business name</label>
        <input id="business_name" name="business_name" value="{{ old('business_name', $lead->business_name) }}" required>
    </div>
    <div>
        <label for="website_url">Website URL</label>
        <input id="website_url" name="website_url" value="{{ old('website_url', $lead->website_url) }}" required>
    </div>
    <div>
        <label for="category">Category</label>
        <input id="category" name="category" value="{{ old('category', $lead->category) }}">
    </div>
    <div>
        <label for="status">Status</label>
        <select id="status" name="status" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $lead->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="city">City</label>
        <input id="city" name="city" value="{{ old('city', $lead->city) }}">
    </div>
    <div>
        <label for="country">Country</label>
        <input id="country" name="country" value="{{ old('country', $lead->country) }}">
    </div>
    <div>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $lead->email) }}">
    </div>
    <div>
        <label for="phone">Phone</label>
        <input id="phone" name="phone" value="{{ old('phone', $lead->phone) }}">
    </div>
    <div>
        <label for="source">Source</label>
        <input id="source" name="source" value="{{ old('source', $lead->source) }}">
    </div>
    <div>
        <label for="source_url">Source URL</label>
        <input id="source_url" name="source_url" value="{{ old('source_url', $lead->source_url) }}">
    </div>
    <div class="form-row-full">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes">{{ old('notes', $lead->notes) }}</textarea>
    </div>
</div>

<div class="actions" style="margin-top: 16px;">
    <button type="submit">Save Lead</button>
    <a class="button secondary" href="{{ route('admin.leads.index') }}">Cancel</a>
</div>

