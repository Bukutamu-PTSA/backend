import json

PATH = "/home/jak/code/kemnaker/bukutamu_ptsa_rebuild/ptsa-kemnaker.hopscotch_collection.json"

with open(PATH, "r", encoding="utf-8") as f:
    data = json.load(f)


def fix_request(req):
    # Request version must be a stringified number per Hoppscotch schema
    req["v"] = "13"
    # Ensure all required fields exist for strict safeParse
    req.setdefault("params", [])
    req.setdefault("headers", [])
    req.setdefault("auth", {"authType": "inherit", "authActive": True})
    req.setdefault("body", {"contentType": None, "body": None})
    req.setdefault("preRequestScript", "")
    req.setdefault("testScript", "")
    req.setdefault("requestVariables", [])
    # v13 request schema requires a `responses` map
    req.setdefault("responses", {})
    return req


def fix_collection(coll):
    # Collection version: latest is 12
    coll["v"] = 12
    coll.setdefault("name", "Untitled")
    coll.setdefault("folders", [])
    coll.setdefault("requests", [])
    coll.setdefault("auth", {"authType": "inherit", "authActive": True})
    coll.setdefault("headers", [])
    coll.setdefault("variables", [])
    coll["requests"] = [fix_request(r) for r in coll.get("requests", [])]
    coll["folders"] = [fix_collection(fdr) for fdr in coll.get("folders", [])]
    return coll


data = fix_collection(data)

with open(PATH, "w", encoding="utf-8") as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

# Report
def count(coll):
    n = len(coll.get("requests", []))
    for fdr in coll.get("folders", []):
        n += count(fdr)
    return n

print("OK. Total requests:", count(data))
