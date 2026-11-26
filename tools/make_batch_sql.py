marquee_names = [
    'Virat Kohli', 'Rohit Sharma', 'KL Rahul', 'Shreyas Iyer',
    'Suryakumar Yadav', 'Jasprit Bumrah', 'Rishabh Pant', 'M S Dhoni',
    'Hardik Pandya', 'MS Dhoni'
]

with open('database/all_682_players.sql', 'r', encoding='utf-8') as f:
    lines = f.readlines()

values = []
for line in lines:
    line = line.strip()
    if line.startswith('INSERT INTO players'):
        v_idx = line.find('VALUES')
        if v_idx != -1:
            val_part = line[v_idx + 6:].strip().rstrip(';')
            for m in marquee_names:
                if f"'{m}'" in val_part:
                    val_part = val_part.replace(", 'A', ", ", 'Marquee', ")
                    break
            values.append(val_part)

output = (
    "USE ipl_auction;\n\n"
    "DELETE FROM players;\n\n"
    "INSERT INTO players (player_name, player_type, player_role, base_price, auction_group, nationality, age) VALUES\n"
    + ",\n".join(values) + ";\n"
)

with open('database/all_players_batch.sql', 'w', encoding='utf-8') as f:
    f.write(output)

print(f"Generated {len(values)} player rows into database/all_players_batch.sql")

