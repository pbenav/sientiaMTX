import os
import glob

views = glob.glob('resources/views/metrics/**/*.blade.php', recursive=True)

for view in views:
    if 'index.blade.php' in view and view == 'resources/views/metrics/index.blade.php':
        continue
    if 'daily.blade.php' in view:
        continue

    with open(view, 'r') as f:
        content = f.read()

    if "@extends('metrics.layouts.app')" in content:
        # We need to extract the title if possible, or just default to Dashboard
        import re
        title_match = re.search(r"@section\('header-title',\s*'(.*?)'\)", content)
        title = title_match.group(1) if title_match else "Dashboard"
        
        # Remove old layout tags
        content = re.sub(r"@extends\('metrics\.layouts\.app'\)\s*", "", content)
        content = re.sub(r"@section\('title',.*?\)\s*", "", content)
        content = re.sub(r"@section\('header-title',.*?\)\s*", "", content)
        content = re.sub(r"@section\('content'\)\s*", "", content)
        
        # Replace @endsection at the end before scripts with </x-app-layout>
        # Actually it's easier to just find the last @endsection and replace it
        if "@push('scripts')" in content:
            content = content.replace("@push('scripts')", "</x-app-layout>\n@push('scripts')", 1)
            content = content.replace("@endsection\n", "", 1)
            content = content.replace("@endsection", "", 1)
        else:
            # reverse replace @endsection
            rsplit = content.rsplit('@endsection', 1)
            if len(rsplit) > 1:
                content = rsplit[0] + '</x-app-layout>' + rsplit[1]

        header_block = f"""<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-wider">
            {{{{ __('{title}') }}}}
        </h2>
    </x-slot>
"""
        content = header_block + content

        with open(view, 'w') as f:
            f.write(content)
        print(f"Updated {view}")

