#:package MemoryPack@1.21.4

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

if (args[0] == "utf16-write")
{
    var payload = new Utf16Payload
    {
        Name = "雷少",
    };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "utf16-read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<Utf16Payload>(Convert.FromBase64String(args[1]));
    if (payload is null)
    {
        Console.Error.WriteLine("Payload was null.");
        return 1;
    }

    Assert(payload.Name == "雷少", "name");

    Console.WriteLine("ok");
    return 0;
}

if (args[0] == "package-write")
{
    var payload = new PackageData
    {
        Selections = new[]
        {
            new[] { new PackageItem { Id = 1 }, new PackageItem { Id = 2 } },
            new[] { new PackageItem { Id = 3 } },
        },
    };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "package-read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<PackageData>(Convert.FromBase64String(args[1]));
    if (payload is null)
    {
        Console.Error.WriteLine("Payload was null.");
        return 1;
    }

    Assert(payload.Selections.Length == 2, "selections length");
    Assert(payload.Selections[0].Length == 2, "first selection length");
    Assert(payload.Selections[1].Length == 1, "second selection length");
    Assert(payload.Selections[0][0].Id == 1, "item 1");
    Assert(payload.Selections[0][1].Id == 2, "item 2");
    Assert(payload.Selections[1][0].Id == 3, "item 3");

    Console.WriteLine("ok");
    return 0;
}

if (args[0] == "point-write")
{
    var payload = new Point { X = 1, Y = 2 };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "point-read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<Point>(Convert.FromBase64String(args[1]));

    Assert(payload.X == 1 && payload.Y == 2, "point");

    Console.WriteLine("ok");
    return 0;
}

if (args[0] == "shape-write")
{
    var payload = new Shape
    {
        Origin = new Point { X = 1, Y = 2 },
        Points = new[] { new Point { X = 3, Y = 4 }, new Point { X = 5, Y = 6 } },
    };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "shape-read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<Shape>(Convert.FromBase64String(args[1]));
    if (payload is null)
    {
        Console.Error.WriteLine("Payload was null.");
        return 1;
    }

    Assert(payload.Origin.X == 1 && payload.Origin.Y == 2, "shape origin");
    Assert(payload.Points is [{ X: 3, Y: 4 }, { X: 5, Y: 6 }], "shape points");

    Console.WriteLine("ok");
    return 0;
}

if (args[0] == "point-grid-write")
{
    var payload = new PointGrid
    {
        Matrix = new[]
        {
            new[] { new Point { X = 1, Y = 2 }, new Point { X = 3, Y = 4 } },
            new[] { new Point { X = 5, Y = 6 } },
        },
    };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "point-grid-read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<PointGrid>(Convert.FromBase64String(args[1]));
    if (payload is null)
    {
        Console.Error.WriteLine("Payload was null.");
        return 1;
    }

    Assert(payload.Matrix.Length == 2, "point grid rows");
    Assert(payload.Matrix[0] is [{ X: 1, Y: 2 }, { X: 3, Y: 4 }], "point grid row 1");
    Assert(payload.Matrix[1] is [{ X: 5, Y: 6 }], "point grid row 2");

    Console.WriteLine("ok");
    return 0;
}

if (args[0] == "inventory-write")
{
    var payload = new Inventory
    {
        Counts = new Dictionary<string, int> { ["sword"] = 2, ["potion"] = 5 },
        Locations = new Dictionary<string, Point> { ["spawn"] = new Point { X = 9, Y = 4 } },
    };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "inventory-read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<Inventory>(Convert.FromBase64String(args[1]));
    if (payload is null)
    {
        Console.Error.WriteLine("Payload was null.");
        return 1;
    }

    Assert(payload.Counts.Count == 2 && payload.Counts["sword"] == 2 && payload.Counts["potion"] == 5, "inventory counts");
    Assert(payload.Locations.Count == 1 && payload.Locations["spawn"].X == 9 && payload.Locations["spawn"].Y == 4, "inventory locations");

    Console.WriteLine("ok");
    return 0;
}

if (args[0] == "union-write")
{
    var payload = new UnionZoo
    {
        Favorite = new UnionCat { Lives = 9 },
        Animals = new IUnionAnimal[]
        {
            new UnionCat { Lives = 7 },
            new UnionDog { Name = "pochi" },
        },
    };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "union-read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<UnionZoo>(Convert.FromBase64String(args[1]));
    if (payload is null)
    {
        Console.Error.WriteLine("Payload was null.");
        return 1;
    }

    Assert(payload.Favorite is UnionCat { Lives: 9 }, "union favorite");
    Assert(payload.Animals is [UnionCat { Lives: 7 }, UnionDog { Name: "pochi" }], "union animals");

    Console.WriteLine("ok");
    return 0;
}

if (args[0] == "union-value-write")
{
    IUnionAnimal payload = new UnionCat { Lives = 9 };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "union-value-read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<IUnionAnimal>(Convert.FromBase64String(args[1]));

    Assert(payload is UnionCat { Lives: 9 }, "union value");

    Console.WriteLine("ok");
    return 0;
}

if (args[0] == "abstract-union-write")
{
    AbstractUnionShape payload = new UnionCircle { Radius = 8 };

    Console.WriteLine(Convert.ToBase64String(MemoryPackSerializer.Serialize(payload)));
    return 0;
}

if (args[0] == "abstract-union-read")
{
    if (args.Length < 2)
    {
        Console.Error.WriteLine("Missing base64 payload.");
        return 2;
    }

    var payload = MemoryPackSerializer.Deserialize<AbstractUnionShape>(Convert.FromBase64String(args[1]));

    Assert(payload is UnionCircle { Radius: 8 }, "abstract union circle");

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

[MemoryPackable]
public partial class Shape
{
    public Point Origin { get; set; }

    public Point[] Points { get; set; } = Array.Empty<Point>();
}

[MemoryPackable]
public partial class PointGrid
{
    public Point[][] Matrix { get; set; } = Array.Empty<Point[]>();
}

[MemoryPackable]
public partial class Inventory
{
    public Dictionary<string, int> Counts { get; set; } = new();

    public Dictionary<string, Point> Locations { get; set; } = new();
}

[MemoryPackable]
[MemoryPackUnion(0, typeof(UnionCat))]
[MemoryPackUnion(250, typeof(UnionDog))]
public partial interface IUnionAnimal
{
}

[MemoryPackable]
public partial class UnionCat : IUnionAnimal
{
    public int Lives { get; set; }
}

[MemoryPackable]
public partial class UnionDog : IUnionAnimal
{
    public string Name { get; set; } = "";
}

[MemoryPackable]
public partial class UnionZoo
{
    public IUnionAnimal? Favorite { get; set; }

    public IUnionAnimal[] Animals { get; set; } = Array.Empty<IUnionAnimal>();
}

[MemoryPackable]
[MemoryPackUnion(1, typeof(UnionCircle))]
public abstract partial class AbstractUnionShape
{
}

[MemoryPackable]
public partial class UnionCircle : AbstractUnionShape
{
    public int Radius { get; set; }
}

[MemoryPackable]
public partial class Utf16Payload
{
    [Utf16StringFormatter]
    public string Name { get; set; } = "";
}

[MemoryPackable]
public partial class PackageData
{
    public PackageItem[][] Selections { get; set; } = Array.Empty<PackageItem[]>();
}

[MemoryPackable]
public partial class PackageItem
{
    public int Id { get; set; }
}
