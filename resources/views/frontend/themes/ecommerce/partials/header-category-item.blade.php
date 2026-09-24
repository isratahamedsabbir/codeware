{{--
    One row of the header's Categories dropdown, plus (collapsed) its child
    categories — rendered recursively. A category with children gets a +/−
    toggle before its name; the toggle stops its click from bubbling so it
    expands the branch instead of closing the dropdown.

    @param  \App\Models\ProductCategory  $category
    @param  \Illuminate\Support\Collection  $childrenByParent  parent_id => children
    @param  int  $depth
--}}
@php $children = $childrenByParent->get($category->id, collect()); @endphp

<div @if ($children->isNotEmpty()) x-data="{ expanded: false }" @endif>
    <div class="flex items-center" style="padding-left: {{ $depth * 0.875 }}rem">
        @if ($children->isNotEmpty())
            <button type="button" @click.stop="expanded = ! expanded"
                :aria-expanded="expanded"
                aria-label="{{ __('Show :name subcategories', ['name' => $category->name]) }}"
                class="ml-2.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md! text-zinc-500 transition-colors hover:bg-gray-100 hover:text-brand">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                    <path x-show="! expanded" stroke-linecap="round" stroke-linejoin="round" d="M12 5v14" />
                </svg>
            </button>
        @else
            {{-- Keeps names aligned with those that have a toggle. --}}
            <span class="ml-2.5 h-6 w-6 shrink-0"></span>
        @endif

        <a href="{{ route('shop.category', $category->slug) }}"
            class="block min-w-0 flex-1 truncate py-2 pl-1.5 pr-4 text-sm font-semibold text-zinc-700 transition-colors hover:text-brand">
            {{ $category->name }}
        </a>
    </div>

    @if ($children->isNotEmpty())
        <div x-show="expanded" x-collapse x-cloak class="border-l border-zinc-100" style="margin-left: {{ $depth * 0.875 + 1.375 }}rem">
            @foreach ($children as $child)
                @include('frontend.themes.ecommerce.partials.header-category-item', [
                    'category' => $child,
                    'childrenByParent' => $childrenByParent,
                    'depth' => 0,
                ])
            @endforeach
        </div>
    @endif
</div>
