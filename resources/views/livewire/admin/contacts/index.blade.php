<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Filters --}}
    <div class="flex gap-3 px-6 py-3 flex-wrap items-center">
        <x-per-page-select :options="$this->perPageOptions()" />
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="unread">Unread</option>
            <option value="read">Read</option>
        </select>
        <div class="relative flex-1 min-w-[180px]">
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
                    <col class="hidden lg:table-column" style="width:5%">
                    <col style="width:15%">
                    <col class="hidden lg:table-column" style="width:14%">
                    <col style="width:14%">
                    <col style="width:22%">
                    <col class="hidden lg:table-column" style="width:11%">
                    <col style="width:14%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Contact Info</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Message</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($contacts as $contact)
                        <tr class="hover:bg-indigo-50/30 transition-colors">

                            {{-- Expand toggle (small screens only, where columns are hidden) --}}
                            <td class="px-2 py-2 text-center">
                                <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingMessageId === $contact->id"
                                    wire:click="{{ $viewingMessageId === $contact->id ? 'closeMessage' : 'viewMessage('.$contact->id.')' }}" />
                            </td>

                            {{-- ID --}}
                            <td class="hidden lg:table-cell px-4 py-2">
                                <span class="text-sm text-zinc-500 font-mono">{{ $contact->id }}</span>
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

                            {{-- Status --}}
                            <td class="px-4 py-2">
                                <select wire:change="updateStatus({{ $contact->id }}, $event.target.value)"
                                    class="text-sm rounded-lg border border-zinc-200 bg-white py-1.5 pl-2.5 pr-7 text-zinc-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all appearance-none"
                                    style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 8px center">
                                    <option value="unread" @selected($contact->status === 'unread')>Unread</option>
                                    <option value="read" @selected($contact->status === 'read')>Read</option>
                                </select>
                            </td>

                        </tr>
                        @if ($viewingMessageId === $contact->id)
                            <x-admin-row-details colspan="8">
                                <x-admin-row-details.item label="Email">{{ $contact->email }}</x-admin-row-details.item>
                                @if ($contact->phone_number)
                                    <x-admin-row-details.item label="Phone">{{ $contact->phone_number }}</x-admin-row-details.item>
                                @endif
                                <x-admin-row-details.item label="Date">{{ $contact->created_at->toDisplay() }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
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

</div>
