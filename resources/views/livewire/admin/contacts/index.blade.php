{{-- A single root element wraps the whole file (Livewire requires exactly
     one) — the page heading below and the card after it used to be two
     top-level sibling divs, which meant only one of them was actually
     inside Livewire's tracked root and the other silently stopped updating
     after the first render. --}}
<div>

    {{-- Page heading is rendered here (layouts.admin's own is disabled via
         hidePageHeading in Index::render()) rather than the normal
         @stack('page-header-actions') flow (this page never used that stack —
         Contacts is read-only, there was never a "New contact" button) because
         the Export button below depends on reactive Livewire state
         ($selectedIds), which a @push('page-header-actions') block would
         never update again after the first wire:click round trip. --}}
    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.contacts'])
        </div>
        @if (count($selectedIds) > 0)
            <div class="flex items-center gap-2 shrink-0">
                <flux:button variant="outline" size="sm" icon="arrow-down-tray"
                    href="{{ route('admin.contacts.export', ['ids' => $selectedIds]) }}">
                    Export ({{ count($selectedIds) }})
                </flux:button>
            </div>
        @endif
    </div>

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Filters --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="unread">Unread</option>
            <option value="read">Read</option>
        </select>
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, email or subject…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <colgroup>
                    <col style="width:5%">
                    <col style="width:5%">
                    <col class="hidden lg:table-column" style="width:5%">
                    <col style="width:10%">
                    <col class="hidden lg:table-column" style="width:14%">
                    <col style="width:14%">
                    <col style="width:10%">
                    <col class="hidden lg:table-column" style="width:9%">
                    <col style="width:12%">
                    <col style="width:12%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Contact Info</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Message</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($contacts as $contact)
                        <tr wire:key="contact-{{ $contact->id }}"
                            class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ in_array($contact->id, $selectedIds, true) ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                            @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $contact->id }}) }">

                            {{-- Expand toggle, small screens only, where columns are hidden. --}}
                            <td class="px-1 py-2 text-center">
                                <div class="flex items-center justify-center gap-1" @click.stop>
                                    <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingMessageId === $contact->id"
                                        wire:click="{{ $viewingMessageId === $contact->id ? 'closeMessage' : 'viewMessage('.$contact->id.')' }}" />
                                </div>
                            </td>

                            {{-- Bulk-select checkbox — its own dedicated column so it never
                                 crowds into neighboring cells; stays hidden until a selection
                                 is already in progress. --}}
                            <td class="px-1 py-2 text-center">
                                <div @click.stop>
                                    @if (count($selectedIds) > 0)
                                        <input type="checkbox" wire:click="toggleSelect({{ $contact->id }})"
                                            @checked(in_array($contact->id, $selectedIds, true))
                                            class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                    @endif
                                </div>
                            </td>

                            {{-- ID --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <x-copy-text :text="$contact->id" class="font-mono text-sm text-zinc-500">{{ $contact->id }}</x-copy-text>
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-2">
                                <span class="font-medium text-zinc-900 text-sm"><x-truncate :text="$contact->full_name" /></span>
                            </td>

                            {{-- Contact Info --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <div class="text-sm text-zinc-600"><x-truncate :text="$contact->email" /></div>
                                @if ($contact->phone_number)
                                    <div class="text-xs text-zinc-400 mt-0.5">{{ $contact->phone_number }}</div>
                                @endif
                            </td>

                            {{-- Subject --}}
                            <td class="px-4 py-2">
                                <span class="text-sm text-zinc-600 truncate block"><x-truncate :text="$contact->subject" /></span>
                            </td>

                            {{-- Message --}}
                            <td class="px-4 py-2">
                                <p class="text-sm text-zinc-500 truncate"><x-truncate :text="$contact->message" /></p>
                            </td>

                            {{-- Date --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <span class="text-sm text-zinc-500 whitespace-nowrap">{{ $contact->created_at->toDisplay() }}</span>
                            </td>

                            {{-- Status — "Read" is one-way: an unread contact shows as a
                                 clickable badge that marks it read; once read, it becomes
                                 a static badge and can never be flipped back to "Unread"
                                 (updateStatus() also guards this server-side). --}}
                            <td class="px-4 py-2">
                                @if ($contact->status === 'read')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-600 border border-zinc-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                                        Read
                                    </span>
                                @else
                                    <button type="button" wire:click="updateStatus({{ $contact->id }}, 'read')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600 border border-amber-200 hover:bg-amber-100 transition-colors cursor-pointer">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Unread
                                    </button>
                                @endif
                            </td>

                            {{-- Actions — disabled while a bulk selection is active, so the
                                 per-row actions can't conflict with the bulk toolbar above. --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['wireClick' => 'showContact(' . $contact->id . ')', 'icon' => 'eye', 'label' => 'View', 'color' => 'primary', 'disabled' => count($selectedIds) > 0],
                                    ['wireClick' => 'openCustomEmailFor(' . $contact->id . ')', 'icon' => 'envelope', 'label' => 'Send Email', 'color' => 'cyan-500', 'disabled' => count($selectedIds) > 0],
                                ]" />
                            </td>

                        </tr>
                        @if ($viewingMessageId === $contact->id)
                            <x-admin-row-details colspan="10">
                                <x-admin-row-details.item label="Email">{{ $contact->email }}</x-admin-row-details.item>
                                @if ($contact->phone_number)
                                    <x-admin-row-details.item label="Phone">{{ $contact->phone_number }}</x-admin-row-details.item>
                                @endif
                                <x-admin-row-details.item label="Date">{{ $contact->created_at->toDisplay() }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
                                </svg>
                                <p class="text-sm text-zinc-600">No contacts found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $contacts->links() }}
    </div>

    {{-- View Modal — full, untruncated contact submission. $viewingContactId is
         looked up against the already-loaded $contacts page rather than a
         fresh query, since it can only ever point at a row rendered above. --}}
    <flux:modal name="contact-view" class="md:w-lg"
        x-on:open-modal.window="if ($event.detail.name === 'contact-view') $flux.modal('contact-view').show()"
        x-on:close-modal.window="if ($event.detail.name === 'contact-view') $flux.modal('contact-view').close()">
        @php $viewingContact = $contacts->firstWhere('id', $viewingContactId); @endphp
        @if ($viewingContact)
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="4" width="20" height="16" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg>
                    </div>
                    <flux:heading>Contact Message</flux:heading>
                </div>

                <div class="divide-y divide-zinc-100">
                    <x-admin-row-details.item label="Name">{{ $viewingContact->full_name }}</x-admin-row-details.item>
                    <x-admin-row-details.item label="Email">{{ $viewingContact->email }}</x-admin-row-details.item>
                    @if ($viewingContact->phone_number)
                        <x-admin-row-details.item label="Phone">{{ $viewingContact->phone_number }}</x-admin-row-details.item>
                    @endif
                    <x-admin-row-details.item label="Subject">{{ $viewingContact->subject }}</x-admin-row-details.item>
                    <x-admin-row-details.item label="Status">{{ ucfirst($viewingContact->status) }}</x-admin-row-details.item>
                    <x-admin-row-details.item label="Date">{{ $viewingContact->created_at->toDisplay() }}</x-admin-row-details.item>
                </div>

                <div>
                    <span class="text-xs text-zinc-400 uppercase tracking-wider">Message</span>
                    <p class="mt-1 text-sm text-zinc-800 whitespace-pre-line">{{ $viewingContact->message }}</p>
                </div>

                <div class="flex gap-2 pt-1">
                    <flux:button size="sm" variant="primary" icon="envelope" wire:click="openCustomEmailFor({{ $viewingContact->id }})">
                        Reply by Email
                    </flux:button>
                    <flux:modal.close>
                        <flux:button size="sm" variant="ghost">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Send a one-off reply email — pre-filled by openCustomEmailFor() with
         the viewed contact's email and subject. --}}
    <flux:modal name="send-custom-email" class="md:w-120"
        x-on:open-modal.window="if ($event.detail.name === 'send-custom-email') $flux.modal('send-custom-email').show()"
        x-on:close-modal.window="if ($event.detail.name === 'send-custom-email') $flux.modal('send-custom-email').close()">
        <div class="space-y-5">
            <flux:heading>{{ __('Send Email') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">
                {{ __('Sends a one-off email using whatever mail settings are currently saved. Pick a template to send with it, or leave it blank for a freeform email.') }}
            </flux:text>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Email') }}</flux:label>
                    <flux:input type="email" wire:model="customEmailTo" placeholder="you@example.com" />
                    <flux:error name="customEmailTo" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Template') }}</flux:label>
                    <flux:select wire:model.live="customEmailTemplateKey">
                        <flux:select.option value="">{{ __('Custom email (no template)') }}</flux:select.option>
                        @foreach ($this->customEmailTemplates() as $template)
                            <flux:select.option value="{{ $template->key }}">{{ $template->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="customEmailTemplateKey" />
                </flux:field>

                @if ($customEmailTemplateKey !== '')
                    <flux:field>
                        <flux:label>{{ __('Variables (JSON)') }}</flux:label>
                        <flux:textarea wire:model="customEmailVariables" rows="5" placeholder='{"name": "Rahim"}' class="font-mono text-xs" />
                        <flux:error name="customEmailVariables" />
                        <flux:description>{{ __('Values for the template placeholders. Pre-filled with the template\'s declared variables.') }}</flux:description>
                    </flux:field>
                @else
                    <flux:field>
                        <flux:label>{{ __('Subject') }}</flux:label>
                        <flux:input type="text" wire:model="customEmailSubject" placeholder="Subject" />
                        <flux:error name="customEmailSubject" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="customEmailDescription" rows="6" placeholder="Message" />
                        <flux:error name="customEmailDescription" />
                    </flux:field>
                @endif
            </div>

            <div class="flex gap-2 pt-1">
                <flux:button size="sm" variant="primary" wire:click="sendCustomEmail" wire:loading.attr="disabled" wire:target="sendCustomEmail">
                    {{ __('Send Email') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>

</div>
