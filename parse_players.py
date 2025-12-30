# IPL Auction Player Data Parser
# This script parses the CSV file and generates SQL insert statements

import csv
import re

def clean_name(name):
    """Clean player name"""
    return name.strip().title() if name else ""

def determine_player_type(country, cap_status):
    """Determine player type based on country and capped status"""
    country = country.strip().lower() if country else ""
    cap_status = cap_status.strip() if cap_status else ""
    
    is_indian = country == "india"
    is_capped = cap_status.lower() == "capped"
    
    if is_indian and is_capped:
        return "Indian"
    elif is_indian and not is_capped:
        return "Indian Uncapped"
    elif not is_indian and is_capped:
        return "Overseas"
    else:
        return "Overseas Uncapped"

def map_role(specialism):
    """Map specialism to player role"""
    spec = specialism.upper() if specialism else ""
    
    if "WICKETKEEPER" in spec or "WK" in spec:
        return "Wicket-Keeper"
    elif "ALL-ROUNDER" in spec or "ALLROUNDER" in spec:
        return "All-Rounder"
    elif "BATTER" in spec or "BATSMAN" in spec:
        return "Batsman"
    elif "BOWLER" in spec:
        return "Bowler"
    else:
        return "All-Rounder"

def determine_group(base_price):
    """Classify players into groups A,B,C,D based on base price"""
    try:
        price = float(base_price) * 100000  # Convert lakhs to rupees
        
        if price >= 15000000:  # 15 Cr+
            return 'A'
        elif price >= 8000000:  # 8-15 Cr
            return 'B'
        elif price >= 4000000:  # 4-8 Cr
            return 'C'
        else:  # < 4 Cr
            return 'D'
    except:
        return 'D'

# Read CSV and generate SQL
sql_statements = []
player_count = 0

with open('1731674068078_TATA IPL 2025- Auction List -15.11.24.csv', 'r', encoding='utf-8') as f:
    reader = csv.reader(f)
    
    for row_num, row in enumerate(reader):
        # Skip header rows (first 6 rows)
        if row_num < 6:
            continue
        
        try:
            # Parse columns
            first_name = clean_name(row[3]) if len(row) > 3 else ""
            surname = clean_name(row[4]) if len(row) > 4 else ""
            country = row[5] if len(row) > 5 else ""
            age = row[8] if len(row) > 8 else "25"
            specialism = row[9] if len(row) > 9 else ""
            batting = row[10] if len(row) > 10 else ""
            cap_status = row[19] if len(row) > 19 else "Capped"
            base_price_lakhs = row[20] if len(row) > 20 else "50"
            previous_team = row[17] if len(row) > 17 else ""
            
            # Skip empty rows
            if not first_name or not surname:
                continue
            
            player_name = f"{first_name} {surname}"
            player_type = determine_player_type(country, cap_status)
            player_role = map_role(specialism)
            
            try:
                base_price = int(float(base_price_lakhs) * 100000)  # Convert lakhs to rupees
            except:
                base_price = 5000000
            
            auction_group = determine_group(base_price_lakhs)
            nationality = country.strip().title() if country else "India"
            
            try:
                age = int(age)
            except:
                age = 25
            
            # Clean previous team
            prev_team = previous_team.strip()[:50] if previous_team else ""
            
            # Generate SQL
            sql = f"('{player_name}', '{player_type}', '{player_role}', {base_price}, '{auction_group}', '{prev_team}', '{nationality}', {age})"
            sql_statements.append(sql)
            player_count += 1
            
            # Limit to 100 players for manageable dataset
            if player_count >= 100:
                break
                
        except Exception as e:
            continue

# Output SQL
print(f"-- Generated {player_count} players from CSV")
print()
print("INSERT INTO players (player_name, player_type, player_role, base_price, auction_group, previous_team, nationality, age) VALUES")
print(",\n".join(sql_statements[:100]) + ";")
print()
print(f"-- Total players: {len(sql_statements)}")
