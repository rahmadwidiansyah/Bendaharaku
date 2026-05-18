import os

files = [
    "resources/js/Pages/Loans/Index.vue",
    "resources/js/Pages/Categories/Show.vue",
    "resources/js/Pages/Categories/Edit.vue",
    "resources/js/Pages/Categories/Create.vue",
    "resources/js/Pages/Wallets/Show.vue",
    "resources/js/Pages/Wallets/Edit.vue",
    "resources/js/Pages/Wallets/Create.vue",
    "resources/js/Pages/Transactions/Edit.vue",
    "resources/js/Pages/Transactions/Create.vue"
]

for f in files:
    path = os.path.join("/home/zackbrawn/Documents/Bendaharaku", f)
    if os.path.exists(path):
        with open(path, 'r') as file:
            content = file.read()
        
        content = content.replace("<AuthenticatedLayout>", "<AuthenticatedLayout :fullWidth=\"true\">")
        content = content.replace("max-w-md mx-auto", "w-full lg:max-w-4xl mx-auto lg:px-8")
        
        with open(path, 'w') as file:
            file.write(content)
        print(f"Updated {f}")
