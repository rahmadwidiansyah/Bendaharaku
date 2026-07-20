COPILOT = b"Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"

if commit.message:
    lines = commit.message.splitlines()

    filtered = []
    for line in lines:
        if line.strip() == COPILOT:
            continue
        filtered.append(line)

    commit.message = b"\n".join(filtered)
