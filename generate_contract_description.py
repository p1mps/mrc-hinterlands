import requests

# API Configuration
API_KEY = "4YYVZ21-QD249V1-MA67EFV-22FXR66"
BASE_URL = "http://localhost:3001/api/v1"
WORKSPACE_SLUG = "battletech-rules"  # The URL slug for your workspace

headers = {
    "Authorization": f"Bearer {API_KEY}",
    "Content-Type": "application/json"
}

# 1. Send a query to the RAG workspace
chat_endpoint = f"{BASE_URL}/workspace/{WORKSPACE_SLUG}/chat"

payload = {
    "message": "Generate a compelling background story for a BattleTech hinterlands mercenary contract. The story should establish the narrative context a mercenary unit receives before taking the job — including who is offering the contract, the political or military situation driving the need, what the employer stands to gain, and what details (or motivations) they may be withholding from the unit.\n\nThe contract background should feel grounded in the BattleTech universe: reference the relevant geopolitical factions, era tensions, or Inner Sphere / Periphery dynamics that make the situation believable. The tone should match BattleTech's gritty, morally complex military fiction — there are no clean heroes, employers have agendas, and the battlefield is shaped by economics and politics as much as firepower.\n\nInclude enough intrigue that the mercenary commander would have reasons to dig deeper or stay cautious, but keep the surface-level briefing plausible enough that a unit would accept the contract. The story should work as a mission briefing document or narrative intro a Game Master could read aloud or hand to players.",
    "mode": "query"  # "query" strictly uses RAG context; "chat" includes general conversational knowledge
}

response = requests.post(chat_endpoint, json=payload, headers=headers)

if response.status_code == 200:
    data = response.json()
    
    print("=== RESPONSE ===")
    print(data.get("textResponse"))
    
    print("\n=== SOURCES ===")
    for source in data.get("sources", []):
        print(f"• Document: {source.get('title')} | Page: {source.get('published')}")
else:
    print(f"Error {response.status_code}: {response.text}")
