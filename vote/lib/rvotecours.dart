import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class RVoteCoursPage extends StatefulWidget {
  @override
  _RVoteCoursPageState createState() => _RVoteCoursPageState();
}

class _RVoteCoursPageState extends State<RVoteCoursPage> {
  List<dynamic> votesEnCours = [];

  @override
  void initState() {
    super.initState();
    _fetchVotesEnCours();
  }

  Future<void> _fetchVotesEnCours() async {
    try {
      final response =
          await http.get(Uri.parse('http://127.0.0.1:8000/api/votes-en-cours'));
      if (response.statusCode == 200) {
        setState(() {
          votesEnCours = json.decode(response.body);
        });
      } else {
        print('Failed to load votes en cours');
      }
    } catch (e) {
      debugPrint('Error fetching votes en cours: $e');
    }
  }

  void _voirPlus(int voteId) {
    // Naviguer vers la page des résultats du vote avec l'ID du vote
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ResultatsVotePage(voteId: voteId),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Votes en Cours'),
      ),
      body: ListView.builder(
        itemCount: votesEnCours.length,
        itemBuilder: (context, index) {
          final vote = votesEnCours[index];
          return ListTile(
            title: Text(vote['nom']),
            subtitle: Text(vote['description']),
            trailing: ElevatedButton(
              onPressed: () {
                _voirPlus(vote['id']);
              },
              child: Text('Voir Plus'),
            ),
          );
        },
      ),
    );
  }
}

class ResultatsVotePage extends StatelessWidget {
  final int voteId;

  ResultatsVotePage({required this.voteId});

  @override
  Widget build(BuildContext context) {
    // Utiliser l'ID du vote pour afficher les résultats du vote
    return Scaffold(
      appBar: AppBar(
        title: Text('Résultats du Vote'),
      ),
      body: Center(
        child: Text('Afficher les résultats pour le vote $voteId ici.'),
      ),
    );
  }
}
