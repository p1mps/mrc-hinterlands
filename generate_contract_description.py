import subprocess
import sys
import json
import requests

# API Configuration
API_KEY = "4YYVZ21-QD249V1-MA67EFV-22FXR66"
BASE_URL = "http://localhost:3001/api/v1"
WORKSPACE_SLUG = "battletech-rules"

def get_contract_from_db(contract_id):
    result = subprocess.run(
        ["php", "bin/console", "dbal:run-sql",
         "SELECT id, name, employer, employer_affiliation, type, scale, "
         "duration_months, base_pay_percent, command_rights, support_terms, "
         "salvage_rights, transport_terms, number_of_tracks, tracks_completed, "
         "status, planet, intensity, is_opposing "
         "FROM contract WHERE id = %s" % contract_id],
        capture_output=True, text=True
    )
    if result.returncode != 0:
        print(f"Database error: {result.stderr}")
        sys.exit(1)

    lines = result.stdout.strip().split("\n")
    if len(lines) < 3:
        print("No contract found with that ID.")
        sys.exit(1)

    headers = [h.strip() for h in lines[1].split()]
    values = [v.strip() for v in lines[2].split()]
    return dict(zip(headers, values))

def generate_description(contract):
    chat_endpoint = f"{BASE_URL}/workspace/{WORKSPACE_SLUG}/chat"

    prompt = (
        "You are a BattleTech hinterlands contract writer. "
        "Using the contract details below, generate a compelling markdown description "
        "that serves as the mission briefing given to a mercenary commander.\n\n"
        "The description should:\n"
        "- Establish the narrative context of the contract\n"
        "- Explain who is offering it and why\n"
        "- Reference relevant BattleTech geopolitical factions and dynamics\n"
        "- Match BattleTech's gritty, morally complex tone\n"
        "- Include enough intrigue to make the mercenary commander cautious\n"
        "- Keep it plausible as a mission briefing\n\n"
        "Format the output in markdown (headings, paragraphs, lists, bold/italic).\n\n"
        "---\n"
        "Contract Details:\n"
    )

    field_labels = {
        "id": "ID",
        "name": "Name",
        "employer": "Employer",
        "employer_affiliation": "Employer Affiliation",
        "type": "Contract Type",
        "scale": "Scale",
        "duration_months": "Duration (months)",
        "base_pay_percent": "Base Pay %",
        "command_rights": "Command Rights",
        "support_terms": "Support Terms",
        "salvage_rights": "Salvage Rights",
        "transport_terms": "Transport Terms",
        "number_of_tracks": "Number of Tracks",
        "tracks_completed": "Tracks Completed",
        "status": "Status",
        "planet": "Planet",
        "intensity": "Intensity",
        "is_opposing": "Is Opposing",
    }

    for key, label in field_labels.items():
        if key in contract:
            value = contract[key]
            if value is None:
                value = "—"
            prompt += f"- {label}: {value}\n"

    payload = {
        "message": prompt,
        "mode": "query",
    }

    headers = {
        "Authorization": f"Bearer {API_KEY}",
        "Content-Type": "application/json",
    }

    response = requests.post(chat_endpoint, json=payload, headers=headers)

    if response.status_code == 200:
        data = response.json()
        print(data.get("textResponse"))
    else:
        print(f"Error {response.status_code}: {response.text}")


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python generate_contract_description.py <contract_id>")
        sys.exit(1)

    contract_id = int(sys.argv[1])
    contract = get_contract_from_db(contract_id)
    generate_description(contract)
