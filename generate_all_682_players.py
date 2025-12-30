import csv

def clean_name(name):
    """Clean player name"""
    return name.strip() if name else ""

def determine_player_type(country, cap_status):
    """Determine player type based on country and capped status"""
    country = country.strip() if country else ""
    cap_status = cap_status.strip() if cap_status else ""
    
    is_indian = country == "India"
    is_uncapped = cap_status == "Uncapped"
    
    if is_indian and is_uncapped:
        return "Indian Uncapped"
    elif is_indian:
        return "Indian"
    elif is_uncapped:
        return "Overseas Uncapped"
    else:
        return "Overseas"

def map_role(specialism):
    """Map specialism to player role"""
    spec = specialism.upper() if specialism else ""
    
    if "WICKETKEEPER" in spec or "WICKET-KEEPER" in spec:
        return "Wicket-Keeper"
    elif "BATTER" in spec or "BATSMAN" in spec:
        return "Batsman"
    elif "BOWLER" in spec:
        return "Bowler"
    elif "ALL-ROUNDER" in spec:
        return "All-Rounder"
    else:
        return "All-Rounder"

def determine_auction_group(base_price):
    """Classify players into groups A,B,C,D based on base price"""
    if base_price >= 20000000:
        return 'A'
    elif base_price >= 12500000:
        return 'B'
    elif base_price >= 7500000:
        return 'C'
    else:
        return 'D'

def escape_sql_string(s):
    """Escape single quotes in SQL strings"""
    return s.replace("'", "''") if s else ""

# Read CSV and generate SQL
sql_statements = []
player_count = 0
group_counts = {'A': 0, 'B': 0, 'C': 0, 'D': 0}

csv_path = r'c:\xampp\htdocs\ipl_auction\1731674068078_TATA IPL 2025- Auction List -15.11.24.csv'

with open(csv_path, 'r', encoding='utf-8') as f:
    reader = csv.reader(f)
    
    for row_num, row in enumerate(reader):
        # Skip first 4 header rows
        if row_num < 4:
            continue
        
        try:
            # Parse columns (using 0-based indexing)
            first_name = clean_name(row[3]) if len(row) > 3 else ""
            surname = clean_name(row[4]) if len(row) > 4 else ""
            country = row[5] if len(row) > 5 else "India"
            age_str = row[8] if len(row) > 8 else "25"
            specialism = row[9] if len(row) > 9 else ""
            cap_status = row[20] if len(row) > 20 else "Capped"
            price_rs_str = row[21] if len(row) > 21 else ""
            
            # Skip empty rows
            if not first_name or not surname:
                continue
            
            # Player Name
            player_name = escape_sql_string(f"{first_name} {surname}")
            
            # Player Type
            player_type = determine_player_type(country, cap_status)
            
            # Player Role
            player_role = map_role(specialism)
            
            # Base Price
            try:
                base_price = int(float(price_rs_str) * 100000)  # Convert lakhs to rupees
            except:
                base_price = 20000000
            
            # Auction Group
            auction_group = determine_auction_group(base_price)
            
            # Nationality
            nationality = escape_sql_string(country.strip() if country else "India")
            
            # Age
            try:
                age = int(age_str)
            except:
                age = 25
            
            # Generate SQL INSERT statement
            sql = f"INSERT INTO players (player_name, player_type, player_role, base_price, auction_group, nationality, age) VALUES ('{player_name}', '{player_type}', '{player_role}', {base_price}, '{auction_group}', '{nationality}', {age});"
            sql_statements.append(sql)
            
            # Update counts
            player_count += 1
            group_counts[auction_group] += 1
                
        except Exception as e:
            print(f"Error processing row {row_num + 1}: {e}")
            continue

# Write to SQL file
output_path = r'c:\xampp\htdocs\ipl_auction\database\all_682_players.sql'

with open(output_path, 'w', encoding='utf-8') as f:
    f.write(f"-- IPL 2025 Auction - All Players\n")
    f.write(f"-- Total Players: {player_count}\n")
    f.write(f"-- Group A (≥20 Cr): {group_counts['A']} players\n")
    f.write(f"-- Group B (12.5-20 Cr): {group_counts['B']} players\n")
    f.write(f"-- Group C (7.5-12.5 Cr): {group_counts['C']} players\n")
    f.write(f"-- Group D (<7.5 Cr): {group_counts['D']} players\n")
    f.write(f"-- Generated on: 2025-12-30\n\n")
    
    for sql in sql_statements:
        f.write(sql + "\n")

print(f"✓ Successfully processed {player_count} players")
print(f"\nDistribution across auction groups:")
print(f"  Group A (≥20 Cr):      {group_counts['A']} players")
print(f"  Group B (12.5-20 Cr):  {group_counts['B']} players")
print(f"  Group C (7.5-12.5 Cr): {group_counts['C']} players")
print(f"  Group D (<7.5 Cr):     {group_counts['D']} players")
print(f"\nSQL file saved to: {output_path}")
