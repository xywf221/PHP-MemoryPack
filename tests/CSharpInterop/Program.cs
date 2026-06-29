using MemoryPack;

if (args.Length < 1)
{
    Console.Error.WriteLine("Usage: write|read [base64]");
    return 2;
}

if (args[0] == "write")
{
    var payload = new InteropPayload
    {
        Id = 42,
        Name = "雷少",
        Active = true,
        Scores = new[] { 3, 5, 8 },
        Tags = new List<string> { "php", "csharp" },
        Counts = new Dictionary<string, int> { ["alpha"] = 10, ["beta"] = 20 },
        Origin = new Point { X = 9, Y = 4 },
    };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<InteropPayload>(Convert.FromBase64String(args[1]));
    if (payload is null)
    {
        Console.Error.WriteLine("Payload was null.");
        return 1;
    }

    Assert(payload.Id == 42, "id");
    Assert(payload.Name == "雷少", "name");
    Assert(payload.Active, "active");
    Assert(payload.Scores is [3, 5, 8], "scores");
    Assert(payload.Tags is ["php", "csharp"], "tags");
    Assert(payload.Counts.Count == 2 && payload.Counts["alpha"] == 10 && payload.Counts["beta"] == 20, "counts");
    Assert(payload.Origin.X == 9 && payload.Origin.Y == 4, "origin");

    Console.WriteLine("ok");
    return 0;
}

Console.Error.WriteLine("Unknown command.");
return 2;

static void Assert(bool condition, string name)
{
    if (!condition)
    {
        throw new InvalidOperationException($"Interop assertion failed: {name}");
    }
}

[MemoryPackable]
public partial class InteropPayload
{
    public int Id { get; set; }

    public string Name { get; set; } = "";

    public bool Active { get; set; }

    public int[] Scores { get; set; } = Array.Empty<int>();

    public List<string> Tags { get; set; } = new();

    public Dictionary<string, int> Counts { get; set; } = new();

    public Point Origin { get; set; }
}

[MemoryPackable]
public partial struct Point
{
    public int X { get; set; }

    public int Y { get; set; }
}
