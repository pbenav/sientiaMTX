import os
import re

pattern = re.compile(r'\n?@media print \{\s*/\* -- Print Mode Code Block Fix -- \*/.*?\n\}\s*\n?', re.DOTALL)

files_to_check = []
for root, dirs, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.blade.php'):
            files_to_check.append(os.path.join(root, file))

for filepath in files_to_check:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if '/* -- Print Mode Code Block Fix -- */' in content:
        # Strip the injected block entirely
        clean_content = pattern.sub('', content)
        
        # Now we reinject it safely.
        # We will look for <style> and replace it. 
        # But we must be careful: if <style> is inside a single quoted JS string, we need to inject it using single quotes and pluses.
        
        # Let's find all <style> tags and their context.
        # It's easier to just use \n for newlines and escape single quotes.
        # Actually, let's just make the CSS one long string with NO single quotes, NO double quotes (except standard ones), and NO newlines.
        minified_css = '@media print{/* -- Print Mode Code Block Fix -- */pre,code,pre *,code *,.prose pre,.prose code{background-color:transparent!important;color:#000!important}div[class*="bg-gray-8"],div[class*="bg-gray-9"],div[class*="bg-slate-8"],div[class*="bg-slate-9"],div[style*="background"],.prose div,.markdown-body div,.bg-gray-800,.bg-gray-900,.dark\\\\:bg-gray-800,.dark\\\\:bg-gray-900{background-color:transparent!important}pre,.prose pre{border:1px solid #cbd5e1!important;border-radius:0.25rem!important;white-space:pre-wrap!important;word-break:break-all!important;padding:0.5rem!important}code,.prose code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace!important}}'
        
        # We will replace '<style>' with '<style>' + minified_css if we know it's safe.
        # To avoid breaking single-quoted strings:
        # If the code has `'<style>'`, we replace it with `'<style>' + '` + minified_css + `'`.
        # If the code has `<style>` inside backticks, it's just `<style>` + minified_css.
        
        new_content = clean_content.replace("'<style>'", "'<style>" + minified_css + "'")
        new_content = new_content.replace('`<style>`', '`<style>' + minified_css + '`')
        new_content = new_content.replace('"<style>"', '"<style>' + minified_css + '"')
        
        # What if it's just <style> not in quotes? (e.g. standard HTML)
        # We can just replace <style> with <style> + minified_css
        # But wait, we already did replace for '<style>' etc.
        # Let's just do a regex that matches <style> and handles the surrounding quotes.
        
        def replacer(match):
            quote = match.group(1) or ''
            return f"{quote}<style>{minified_css}"
            
        new_content = re.sub(r'([\'"\`])?<style>', replacer, clean_content)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Fixed {filepath}")
