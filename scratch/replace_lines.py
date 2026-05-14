import sys

target_file = 'resources/views/surveys/create.blade.php'
new_content_file = 'scratch/new_question_fields.html'

with open(target_file, 'r') as f:
    lines = f.readlines()

with open(new_content_file, 'r') as f:
    new_content = f.read()

# Lines are 1-indexed. Replace lines 81 to 85.
# In 0-indexed list, that's indices 80 to 84 (inclusive).
lines[80:85] = [new_content]

with open(target_file, 'w') as f:
    f.writelines(lines)
