<div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 px-6 py-5 border-b border-zinc-100 flex-wrap">
        <div>
            <h1 class="text-lg font-semibold text-zinc-900">Applications</h1>
            <p class="text-sm text-zinc-600 mt-0.5">Review and manage job applications</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3 px-6 py-3 border-b border-zinc-100 flex-wrap">
        <div class="relative flex-1 min-w-[180px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name or email…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
        <select wire:model.live="jobFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[180px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All jobs</option>
            @foreach ($this->jobs as $job)
                <option value="{{ $job->id }}">{{ $job->getTranslation('title', 'en', false) }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="reviewed">Reviewed</option>
            <option value="shortlisted">Shortlisted</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto px-6 py-4">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full border-collapse" style="table-layout:fixed">
                <colgroup>
                    <col style="width:22%">
                    <col style="width:22%">
                    <col style="width:12%">
                    <col style="width:20%">
                    <col style="width:24%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50 border-b border-zinc-100">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Applicant</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Job</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Applied</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Resume</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr class="border-b border-zinc-50 hover:bg-indigo-50/30 transition-colors">

                            {{-- Applicant --}}
                            <td class="px-4 py-3.5">
                                <div class="font-medium text-zinc-900 text-sm">{{ $application->name }}</div>
                                <div class="text-xs text-zinc-500 mt-0.5">{{ $application->email }}</div>
                                @if ($application->phone)
                                    <div class="text-xs text-zinc-400">{{ $application->phone }}</div>
                                @endif
                            </td>

                            {{-- Job --}}
                            <td class="px-4 py-3.5">
                                @if ($application->job)
                                    <div class="text-sm text-zinc-900 font-medium">{{ $application->job->getTranslation('title', 'en', false) }}</div>
                                    @if ($application->job->department)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-zinc-100 text-zinc-500 mt-1">
                                            {{ $application->job->department->getTranslation('name', 'en', false) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-zinc-300 text-sm">—</span>
                                @endif
                            </td>

                            {{-- Applied --}}
                            <td class="px-4 py-3.5">
                                <span class="text-sm text-zinc-500 whitespace-nowrap">{{ $application->created_at->format('d M Y') }}</span>
                            </td>

                            {{-- Resume --}}
                            <td class="px-4 py-3.5">
                                @if ($application->resume_path)
                                    <a href="{{ route('admin.applications.resume', $application->id) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border bg-green-50 text-green-700 border-green-200 hover:bg-green-100 transition-colors">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="7 10 12 15 17 10" />
                                            <line x1="12" y1="15" x2="12" y2="3" />
                                        </svg>
                                        Download
                                    </a>
                                @else
                                    <span class="text-zinc-300 text-sm">—</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3.5">
                                <select wire:change="updateStatus({{ $application->id }}, $event.target.value)"
                                    class="text-sm rounded-lg border border-zinc-200 bg-white py-1.5 pl-2.5 pr-7 text-zinc-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all appearance-none"
                                    style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 8px center">
                                    <option value="pending" @selected($application->status === 'pending')>Pending</option>
                                    <option value="reviewed" @selected($application->status === 'reviewed')>Reviewed</option>
                                    <option value="shortlisted" @selected($application->status === 'shortlisted')>Shortlisted</option>
                                    <option value="rejected" @selected($application->status === 'rejected')>Rejected</option>
                                </select>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                </svg>
                                <p class="text-sm text-zinc-600">No applications found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3 border-t border-zinc-100">
        {{ $applications->links() }}
    </div>

</div>
