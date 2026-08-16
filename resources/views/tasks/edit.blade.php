<x-layouts.app title="ویرایش تسک">
    <x-page-header title="ویرایش تسک" :description="$task->title" />
    <form method="POST" action="{{ route('tasks.update', $task) }}" class="panel p-4 sm:p-6">
        @csrf
        @method('PUT')
        @include('tasks.form')
    </form>
</x-layouts.app>
