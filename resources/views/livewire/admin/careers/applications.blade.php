<div>
    <div class="mb-3">
        @include('partials._admin-breadcrumbs', ['routeName' => 'admin.career-applications'])
    </div>

    <div class="bg-white rounded-[5px] shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 p-4 flex-wrap">
            <x-per-page-select :options="$this->perPageOptions()" />
            <select wire:model.live="jobFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 bg-white min-w-[160px]">
                <option value="">All jobs</option>
                @foreach ($jobs as $job)
                    <option value="{{ $job->id }}">{{ $job->title }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 bg-white min-w-[140px]">
                <option value="">All statuses</option>
                @foreach (\App\Models\JobApplication::STATUSES as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name or email…"
                class="max-w-xs ml-auto w-full px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Applicant</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Job</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Applied</th>
                        <th class="px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($applications as $application)
                        <tr wire:key="application-{{ $application->id }}" class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 text-sm">{{ $application->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $application->email }} · {{ $application->phone }}</div>
                            </td>
                            <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-600">{{ $application->jobPost?->title ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <select wire:change="setStatus({{ $application->id }}, $event.target.value)"
                                    class="px-2 py-1 text-xs border border-zinc-200 rounded-lg bg-white outline-none focus:border-indigo-400">
                                    @foreach (\App\Models\JobApplication::STATUSES as $status)
                                        <option value="{{ $status }}" @selected($application->status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-500 whitespace-nowrap">{{ $application->created_at->toDisplay() }}</td>
                            <td class="px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['wireClick' => 'view(' . $application->id . ')', 'icon' => 'eye', 'label' => 'Details', 'color' => 'indigo-500'],
                                    ['href' => route('admin.career-applications.resume', $application->id), 'icon' => 'arrow-down-tray', 'label' => 'Download résumé', 'external' => true, 'color' => 'emerald-500'],
                                    ['wireClick' => 'confirmDelete(' . $application->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
                                ]" />
                            </td>
                        </tr>
                        @if ($viewingId === $application->id)
                            <tr wire:key="application-{{ $application->id }}-details" class="bg-zinc-50">
                                <td colspan="5" class="px-6 py-4 text-sm text-zinc-700 space-y-1">
                                    <div><span class="font-semibold">Job:</span> {{ $application->jobPost?->title ?? '—' }}</div>
                                    <div><span class="font-semibold">Résumé:</span> {{ $application->resume_original_name }}</div>
                                    <div class="whitespace-pre-line"><span class="font-semibold">Cover letter:</span> {{ $application->cover_letter ?: '—' }}</div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="px-6 py-16 text-center text-sm text-zinc-600">No applications found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-3">{{ $applications->links() }}</div>
    </div>

    <flux:modal name="application-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'application-delete') $flux.modal('application-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'application-delete') $flux.modal('application-delete').close()">
        <div class="space-y-4">
            <flux:heading>Delete application?</flux:heading>
            <flux:text class="text-sm text-zinc-500">The résumé file is deleted too. This cannot be undone.</flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">Delete</button>
                <flux:modal.close><flux:button size="sm" variant="ghost">Cancel</flux:button></flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
