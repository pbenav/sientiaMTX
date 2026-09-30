import re

with open('resources/css/app.css', 'r', encoding='utf-8') as f:
    content = f.read()

pattern = re.compile(r'@media print \{\s*/\* Make markdown code blocks transparent in print to save toner \*/.*?\n\}\s*\n', re.DOTALL)

NEW_CSS = """@media print {
    /* Make markdown code blocks transparent in print to save toner */
    pre, code, pre *, code *, .prose pre, .prose code {
        background-color: transparent !important;
        color: #000 !important;
    }
    
    /* Remove background from AI assistant wrappers or copied HTML */
    div[class*="bg-gray-8"], div[class*="bg-gray-9"], 
    div[class*="bg-slate-8"], div[class*="bg-slate-9"],
    div[style*="background"],
    .prose div, .markdown-body div,
    .bg-gray-800, .bg-gray-900, .dark\\:bg-gray-800, .dark\\:bg-gray-900 {
        background-color: transparent !important;
    }
    
    pre, .prose pre {
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.25rem !important;
        white-space: pre-wrap !important; /* Ensure long lines wrap in print */
        word-break: break-all !important;
        padding: 0.5rem !important;
    }
    
    code, .prose code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
    }
}
"""

new_content = pattern.sub(NEW_CSS, content)
with open('resources/css/app.css', 'w', encoding='utf-8') as f:
    f.write(new_content)
