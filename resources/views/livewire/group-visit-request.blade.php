<div class="container">
    @if($submitted)
        <div class="card success-card">
            <div class="success-content">
                <div class="check-icon">✓</div>
                <h2>Group Visit Request Submitted!</h2>
                <p>{{ $createdCount }} visitor(s) have been registered and are pending approval.</p>
                <div class="ref-code">{{ $groupId }}</div>
                <p style="font-size: 0.8rem; color: #64748b;">Save this group reference code for your records.</p>
                <button wire:click="resetForm" class="btn-new">Submit Another Group Request</button>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h2>👥 Group Visit Request</h2>
                <p>Register multiple visitors for a single group visit</p>
            </div>
            <form wire:submit="submit">
                {{-- Visit Details --}}
                <div class="section-title">Visit Details</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="host_id">Host Employee *</label>
                        <select id="host_id" wire:model="host_id">
                            <option value="">Select Host...</option>
                            @foreach($hosts as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('host_id') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="site_id">Site *</label>
                        <select id="site_id" wire:model.live="site_id">
                            <option value="">Select Site...</option>
                            @foreach($sites as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('site_id') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="zone_id">Zone</label>
                        <select id="zone_id" wire:model="zone_id">
                            <option value="">Select Zone...</option>
                            @foreach($zones as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="department_id">Department / Destination Unit</label>
                        <select id="department_id" wire:model="department_id">
                            <option value="">Select Department...</option>
                            @foreach($departments as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('department_id') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="purpose">Purpose *</label>
                        <input type="text" id="purpose" wire:model="purpose" placeholder="Purpose of group visit">
                        @error('purpose') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" wire:model="category">
                            <option value="general">General Visit</option>
                            <option value="contractor">Contractor</option>
                            <option value="vendor">Vendor</option>
                            <option value="vip">VIP</option>
                            <option value="job_applicant">Job Applicant</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="scheduled_at">Date & Time *</label>
                        <input type="datetime-local" id="scheduled_at" wire:model="scheduled_at">
                        @error('scheduled_at') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Group Members --}}
                <div class="section-title">
                    Group Members ({{ count($visitors) }})
                    <button type="button" wire:click="addVisitor" class="btn-add">+ Add Visitor</button>
                </div>

                @foreach($visitors as $index => $visitor)
                <div class="visitor-card">
                    <div class="visitor-header">
                        <span>Visitor {{ $index + 1 }}</span>
                        @if(count($visitors) > 1)
                            <button type="button" wire:click="removeVisitor({{ $index }})" class="btn-remove">✕</button>
                        @endif
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" wire:model="visitors.{{ $index }}.full_name" placeholder="Full name">
                            @error("visitors.{$index}.full_name") <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" wire:model="visitors.{{ $index }}.email" placeholder="Email">
                            @error("visitors.{$index}.email") <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Phone *</label>
                            <input type="tel" wire:model="visitors.{{ $index }}.phone" placeholder="Phone">
                            @error("visitors.{$index}.phone") <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Organization *</label>
                            <input type="text" wire:model="visitors.{{ $index }}.organization" placeholder="Organization">
                            @error("visitors.{$index}.organization") <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>ID Type *</label>
                            <select wire:model="visitors.{{ $index }}.id_type">
                                <option value="national_id">National ID</option>
                                <option value="passport">Passport</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="employee_id">Employee ID</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ID Number *</label>
                            <input type="text" wire:model="visitors.{{ $index }}.id_number" placeholder="ID number">
                            @error("visitors.{$index}.id_number") <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                @endforeach

                <button type="submit" class="btn-submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Submit Group Request ({{ count($visitors) }} visitors)</span>
                    <span wire:loading>Processing...</span>
                </button>
            </form>
        </div>
    @endif
    <div style="text-align: center; margin-top: 1rem;">
        <a href="/visit-request" style="color: #64748b; font-size: 0.85rem;">← Individual visit request</a>
    </div>
</div>
