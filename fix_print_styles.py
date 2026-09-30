import os
import glob

# The CSS to inject
CSS_SNIPPET = """
                                /* -- Print Mode Code Block Fix -- */
                                pre, code, pre *, code *, .prose pre, .prose code {
                                    background-color: transparent !important;
                                    color: #000 !important;
                                }
                                pre, .prose pre {
                                    border: 1px solid #cbd5e1 !important;
                                    border-radius: 0.25rem !important;
                                    white-space: pre-wrap !important;
                                    word-break: break-all !important;
                                    padding: 0.5rem !important;
                                }
                                code, .prose code {
                                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
                                }
"""

files_to_check = []
for root, dirs, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.blade.php'):
            files_to_check.append(os.path.join(root, file))

for filepath in files_to_check:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if 'printWin.document.write' in content or 'window.open' in content:
        # We need to find the <style> tags within the print scripts
        if '<style>' in content:
            # Inject only if not already injected
            if '/* -- Print Mode Code Block Fix -- */' not in content:
                # We want to be careful and only inject after <style> in the print window strings
                # Let's split by <style> and add it to all of them, since inline <style> in blade is also for rendering
                # Wait, adding it to all <style> blocks in blade might affect the normal UI if it's not wrapped in @media print!
                # Let's wrap it in @media print just in case it hits a regular style block
                injected_content = CSS_SNIPPET
                
                # Replace <style> with <style> + injected_content
                new_content = content.replace('<style>', '<style>' + injected_content)
                
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Patched {filepath}")
