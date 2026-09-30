with open('resources/views/time-logs/index.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

old_str = """                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            </div>
            
            <!-- Mis Medallas (Compact Widget) -->"""

new_str = """                            </div>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>
            </div>
            
            <!-- Mis Medallas (Compact Widget) -->"""

if old_str in content:
    content = content.replace(old_str, new_str)
    with open('resources/views/time-logs/index.blade.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Replaced!")
else:
    print("Not found!")
