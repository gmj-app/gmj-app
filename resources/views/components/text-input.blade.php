@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 bg-white text-gray-900 placeholder:text-gray-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
