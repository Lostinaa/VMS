<div class="content-container">
    @if($submitted)
        {{-- Success State --}}
        <div class="card">
            <div class="success-card">
                <div class="success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2>Visit Request Submitted!</h2>
                <p>Your request has been received and is pending approval.<br>You will be notified once it has been reviewed.</p>
                <div class="ref-code">{{ $referenceCode }}</div>
                <p style="font-size: 0.8rem; color: #64748b;">Save this reference code for your records.</p>
                <button wire:click="resetForm" class="btn-new">Submit Another Request</button>
            </div>
        </div>
    @else
        {{-- Form --}}
        <div class="card">
            <div class="card-header">
                <h2>Request a Visit</h2>
                <p>Fill in the details below to schedule your visit to an Ethio Telecom facility.</p>
            </div>
            <div class="card-body">
                <form wire:submit="submit">

                    {{-- Personal Information --}}
                    <div class="section-title">Personal Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" wire:model="full_name" placeholder="e.g. Abebe Kebede">
                            @error('full_name') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="organization">Organization *</label>
                            <input type="text" id="organization" wire:model="organization" placeholder="e.g. Ministry of Innovation">
                            @error('organization') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" wire:model="email" placeholder="you@example.com">
                            @error('email') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" wire:model="phone" placeholder="+251 9XX XXX XXX">
                            @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="id_type">ID Type *</label>
                            <select id="id_type" wire:model="id_type">
                                <option value="national_id">National ID</option>
                                <option value="passport">Passport</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="employee_id">Employee ID</option>
                            </select>
                            @error('id_type') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="id_number">ID Number *</label>
                            <input type="text" id="id_number" wire:model="id_number" placeholder="Enter your ID number">
                            @error('id_number') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="car_plate_number">Car Plate Number</label>
                            <input type="text" id="car_plate_number" wire:model="car_plate_number" placeholder="e.g. AA-12345 (if driving)">
                            @error('car_plate_number') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Visit Details --}}
                    <div class="section-title">Visit Details</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="host_id">Who are you visiting? *</label>
                            <select id="host_id" wire:model="host_id">
                                <option value="">— Select Host —</option>
                                @foreach($hosts as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('host_id') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="site_id">Site *</label>
                            <select id="site_id" wire:model.live="site_id">
                                <option value="">— Select Site —</option>
                                @foreach($sites as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('site_id') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        @if($this->zones->isNotEmpty())
                        <div class="form-group">
                            <label for="zone_id">Zone</label>
                            <select id="zone_id" wire:model="zone_id">
                                <option value="">— Select Zone (Optional) —</option>
                                @foreach($this->zones as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="form-group">
                            <label for="department_id">Department / Destination Unit</label>
                            <select id="department_id" wire:model="department_id">
                                <option value="">— Select Department (Optional) —</option>
                                @foreach($departments as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="visitor_type">Visitor Type *</label>
                            <select id="visitor_type" wire:model="visitor_type">
                                <option value="external">External Visitor</option>
                                <option value="internal">Internal (Employee)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="category">Visit Category *</label>
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
                            <label for="scheduled_at">Preferred Date & Time *</label>
                            <input type="datetime-local" id="scheduled_at" wire:model="scheduled_at">
                            @error('scheduled_at') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group span-2">
                            <label for="purpose">Purpose of Visit *</label>
                            <textarea id="purpose" wire:model="purpose" placeholder="Briefly describe the purpose of your visit..." rows="3"></textarea>
                            @error('purpose') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group span-2">
                            <label for="notes">Additional Notes</label>
                            <textarea id="notes" wire:model="notes" placeholder="Any special requirements or information..." rows="2"></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" wire:loading.attr="disabled">
                        <span wire:loading.remove>Submit Visit Request</span>
                        <span wire:loading>Processing...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
    <div style="text-align: center; margin-top: 1rem;">
        <a href="/group-visit" style="color: #64748b; font-size: 0.85rem;">👥 Registering multiple visitors? Use the group visit form →</a>
    </div>
</div>
